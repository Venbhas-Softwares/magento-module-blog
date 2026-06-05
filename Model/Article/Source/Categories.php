<?php
declare(strict_types=1);

namespace Venbhas\Blog\Model\Article\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Venbhas\Blog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;

class Categories implements OptionSourceInterface
{
    /** @var CategoryCollectionFactory */
    private $collectionFactory;

    /**
     * @param CategoryCollectionFactory $collectionFactory
     */
    public function __construct(CategoryCollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Return hierarchical category options for select.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => __('-- Please Select --')]];
        $collection = $this->collectionFactory->create();
        $collection->addFieldToSelect(['category_id', 'name', 'parent_id', 'level'])
            ->setOrder('level', 'ASC')
            ->setOrder('position', 'ASC')
            ->setOrder('name', 'ASC');

        $byParent = [];
        foreach ($collection as $category) {
            $parentId = (int) $category->getParentId();
            $byParent[$parentId][] = $category;
        }

        $this->appendOptions($options, $byParent, 0, 0);

        return $options;
    }

    /**
     * Append hierarchical category options for multiselect fields.
     *
     * @param array $options
     * @param array $byParent
     * @param int $parentId
     * @param int $depth
     * @return void
     */
    private function appendOptions(array &$options, array $byParent, int $parentId, int $depth): void
    {
        if (empty($byParent[$parentId])) {
            return;
        }

        foreach ($byParent[$parentId] as $category) {
            $prefix = $depth > 0 ? str_repeat('— ', $depth) : '';
            $options[] = [
                'value' => (string) $category->getId(),
                'label' => $prefix . $category->getName(),
            ];
            $this->appendOptions($options, $byParent, (int) $category->getId(), $depth + 1);
        }
    }
}
