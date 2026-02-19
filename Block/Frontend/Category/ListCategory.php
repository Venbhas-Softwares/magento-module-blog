<?php

namespace Venbhas\Article\Block\Frontend\Category;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Venbhas\Article\Model\Config;
use Venbhas\Article\Model\ResourceModel\Category\CollectionFactory;

/**
 * Block for category list (sidebar).
 */
class ListCategory extends Template
{
    /** @var CollectionFactory */
    protected $categoryCollectionFactory;

    /** @var Config */
    private $config;

    /** @var StoreManagerInterface */
    private $storeManager;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param CollectionFactory $categoryCollectionFactory
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $categoryCollectionFactory,
        Config $config,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->config = $config;
        $this->storeManager = $storeManager;
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
     * Category view URL (path from store config: Article List URL Key + /category/ + url_key).
     *
     * @param \Venbhas\Article\Model\Category $category
     * @return string
     */
    public function getCategoryUrl($category): string
    {
        $storeId = (int) $this->storeManager->getStore()->getId();
        $basePath = trim($this->config->getArticleListRoute($storeId), '/');
        return $this->getUrl('', ['_direct' => $basePath . '/category/' . $category->getUrlKey()]);
    }
}
