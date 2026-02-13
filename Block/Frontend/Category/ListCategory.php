<?php

namespace Venbhas\Article\Block\Frontend\Category;

use Magento\Framework\View\Element\Template;
use Venbhas\Article\Model\ResourceModel\Category\CollectionFactory;

class ListCategory extends Template
{
    /**
     * @var CollectionFactory
     */
    protected $categoryCollectionFactory;

    public function __construct(
        Template\Context $context,
        CollectionFactory $categoryCollectionFactory,
        array $data = []
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * Get all active categories
     */
    public function getCategoryCollection()
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->addFieldToFilter('status', 1);
        $collection->setOrder('name', 'ASC');

        return $collection;
    }

    /**
     * Get category URL
     */
    public function getCategoryUrl($category): string
    {
        return $this->getUrl('article/category/' . $category->getUrlKey());
    }
}
