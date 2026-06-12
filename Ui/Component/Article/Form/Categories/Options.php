<?php
declare(strict_types=1);

namespace Venbhas\Blog\Ui\Component\Article\Form\Categories;

use Magento\Framework\Data\Collection;
use Magento\Framework\Data\OptionSourceInterface;
use Venbhas\Blog\Model\Category as CategoryModel;
use Venbhas\Blog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;

/**
 * Hierarchical options tree for article category assignment (product-style ui-select).
 */
class Options implements OptionSourceInterface
{
    /** @var CategoryCollectionFactory */
    private $categoryCollectionFactory;

    /** @var array|null */
    private $categoriesTree;

    /**
     * @param CategoryCollectionFactory $categoryCollectionFactory
     */
    public function __construct(CategoryCollectionFactory $categoryCollectionFactory)
    {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        return $this->getCategoriesTree();
    }

    /**
     * Build nested optgroup tree for ui-select.
     *
     * @return array
     */
    private function getCategoriesTree(): array
    {
        if ($this->categoriesTree !== null) {
            return $this->categoriesTree;
        }

        $collection = $this->categoryCollectionFactory->create();
        $collection->addFieldToSelect(['category_id', 'name', 'parent_id', 'path', 'level', 'position', 'status'])
            ->setOrder('level', Collection::SORT_ORDER_ASC)
            ->setOrder('position', Collection::SORT_ORDER_ASC)
            ->setOrder('category_id', Collection::SORT_ORDER_ASC);

        $shownCategoryIds = [];
        foreach ($collection as $category) {
            $categoryId = (int) $category->getId();
            if ($categoryId === CategoryModel::TREE_ROOT_ID) {
                continue;
            }

            $shownCategoryIds[$categoryId] = 1;
            $parentId = (int) $category->getData('parent_id');
            if ($parentId > 0) {
                $shownCategoryIds[$parentId] = 1;
            }

            $path = trim((string) $category->getData('path'), '/');
            if ($path !== '') {
                foreach (explode('/', $path) as $pathId) {
                    if ($pathId !== '') {
                        $shownCategoryIds[(int) $pathId] = 1;
                    }
                }
            }
        }

        $categoryById = [
            CategoryModel::TREE_ROOT_ID => [
                'value' => CategoryModel::TREE_ROOT_ID,
                'optgroup' => null,
            ],
        ];

        foreach ($collection as $category) {
            $categoryId = (int) $category->getId();
            if ($categoryId === CategoryModel::TREE_ROOT_ID || !isset($shownCategoryIds[$categoryId])) {
                continue;
            }

            foreach ([$categoryId, (int) $category->getData('parent_id')] as $nodeId) {
                if ($nodeId === CategoryModel::TREE_ROOT_ID) {
                    continue;
                }
                if (!isset($categoryById[$nodeId])) {
                    $categoryById[$nodeId] = ['value' => $nodeId];
                }
            }

            $parentId = (int) $category->getData('parent_id');
            if ($parentId <= 0) {
                $parentId = CategoryModel::TREE_ROOT_ID;
            }

            $categoryById[$categoryId]['is_active'] = (int) $category->getData('status') === 1 ? 1 : 0;
            $categoryById[$categoryId]['label'] = (string) $category->getData('name');
            $categoryById[$categoryId]['__disableTmpl'] = true;
            $categoryById[$parentId]['optgroup'][] = &$categoryById[$categoryId];
        }

        $this->categoriesTree = $categoryById[CategoryModel::TREE_ROOT_ID]['optgroup'] ?? [];

        return $this->categoriesTree;
    }
}
