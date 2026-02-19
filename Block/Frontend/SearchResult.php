<?php
declare(strict_types=1);

namespace Venbhas\Article\Block\Frontend;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Venbhas\Article\Model\Config;
use Venbhas\Article\Model\ResourceModel\Article\CollectionFactory as ArticleCollectionFactory;
use Venbhas\Article\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;

/**
 * Block for article/category search results.
 */
class SearchResult extends Template
{
    /** @var ArticleCollectionFactory */
    private $articleCollectionFactory;

    /** @var CategoryCollectionFactory */
    private $categoryCollectionFactory;

    /** @var Config */
    private $config;

    /** @var StoreManagerInterface */
    private $storeManager;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param ArticleCollectionFactory $articleCollectionFactory
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        ArticleCollectionFactory $articleCollectionFactory,
        CategoryCollectionFactory $categoryCollectionFactory,
        Config $config,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->articleCollectionFactory = $articleCollectionFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->config = $config;
        $this->storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    /**
     * Get search query from request.
     *
     * @return string
     */
    public function getSearchQuery(): string
    {
        return trim((string) $this->getRequest()->getParam('q', ''));
    }

    /**
     * Get article collection filtered by search query.
     *
     * @return \Venbhas\Article\Model\ResourceModel\Article\Collection
     */
    public function getArticleResults()
    {
        $q = $this->getSearchQuery();
        if ($q === '') {
            return $this->articleCollectionFactory->create()->addFieldToFilter('article_id', 0);
        }
        $collection = $this->articleCollectionFactory->create();
        $collection->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('status', 1)
            ->addFieldToFilter(
                ['title', 'short_description'],
                [
                    ['like' => '%' . $q . '%'],
                    ['like' => '%' . $q . '%']
                ]
            )
            ->setOrder('updated_at', 'desc');
        return $collection;
    }

    /**
     * Get category collection filtered by search query.
     *
     * @return \Venbhas\Article\Model\ResourceModel\Category\Collection
     */
    public function getCategoryResults()
    {
        $q = $this->getSearchQuery();
        if ($q === '') {
            return $this->categoryCollectionFactory->create()->addFieldToFilter('category_id', 0);
        }
        $collection = $this->categoryCollectionFactory->create();
        $collection->addFieldToFilter('status', 1)
            ->addFieldToFilter(
                ['name', 'description'],
                [
                    ['like' => '%' . $q . '%'],
                    ['like' => '%' . $q . '%']
                ]
            )
            ->setOrder('name', 'asc');
        return $collection;
    }

    /**
     * Get article URL by url key.
     *
     * @param string $urlKey
     * @return string
     */
    public function getArticleUrl(string $urlKey): string
    {
        $storeId = (int) $this->storeManager->getStore()->getId();
        $basePath = trim($this->config->getArticleListRoute($storeId), '/');
        return $this->getUrl('', ['_direct' => $basePath . '/' . $urlKey]);
    }

    /**
     * Get category URL by url key.
     *
     * @param string $urlKey
     * @return string
     */
    public function getCategoryUrl(string $urlKey): string
    {
        $storeId = (int) $this->storeManager->getStore()->getId();
        $basePath = trim($this->config->getArticleListRoute($storeId), '/');
        return $this->getUrl('', ['_direct' => $basePath . '/category/' . $urlKey]);
    }
}
