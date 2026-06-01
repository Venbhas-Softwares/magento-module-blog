<?php
declare(strict_types=1);

namespace Venbhas\Blog\Block\Adminhtml\Category;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Tree\Node;
use Magento\Framework\Registry;
use Venbhas\Blog\Model\Category;
use Venbhas\Blog\Model\CategoryFactory;
use Venbhas\Blog\Model\ResourceModel\Category\Tree as CategoryTree;

/**
 * Base block for blog category admin pages.
 */
class AbstractCategory extends Template
{
    /** @var Registry */
    protected $coreRegistry;

    /** @var CategoryTree */
    protected $categoryTree;

    /** @var CategoryFactory */
    protected $categoryFactory;

    /**
     * @param Context $context
     * @param CategoryTree $categoryTree
     * @param Registry $registry
     * @param CategoryFactory $categoryFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        CategoryTree $categoryTree,
        Registry $registry,
        CategoryFactory $categoryFactory,
        array $data = []
    ) {
        $this->categoryTree = $categoryTree;
        $this->coreRegistry = $registry;
        $this->categoryFactory = $categoryFactory;
        parent::__construct($context, $data);
    }

    /**
     * Get current category from registry.
     *
     * @return Category|null
     */
    public function getCategory(): ?Category
    {
        $category = $this->coreRegistry->registry('venbhas_blog_category');
        return $category instanceof Category ? $category : null;
    }

    /**
     * Get selected category id for tree highlighting.
     *
     * @return int
     */
    public function getCategoryId(): int
    {
        $category = $this->getCategory();
        return $category ? (int) $category->getId() : 0;
    }

    /**
     * Get tree root node.
     *
     * @param Node|null $parentNodeCategory
     * @param int $recursionLevel
     * @return Node|null
     */
    public function getRoot(?Node $parentNodeCategory = null, int $recursionLevel = 3): ?Node
    {
        if ($parentNodeCategory !== null && $parentNodeCategory->getId()) {
            return $this->categoryTree->loadNode((int) $parentNodeCategory->getId(), $recursionLevel);
        }

        $root = $this->coreRegistry->registry('venbhas_blog_category_root');
        if ($root === null) {
            $this->categoryTree->load($recursionLevel);
            $root = $this->categoryTree->getRoot();
            if ($root) {
                $root->setIsVisible(true);
            }
            $this->coreRegistry->register('venbhas_blog_category_root', $root);
        }

        return $root;
    }

    /**
     * Get category edit URL base.
     *
     * @return string
     */
    public function getEditUrl(): string
    {
        return $this->getUrl('blog/category/edit', ['_current' => false, 'category_id' => null, 'parent' => null]);
    }
}
