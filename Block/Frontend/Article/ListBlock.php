<?php
declare(strict_types=1);

namespace Venbhas\Article\Block\Frontend\Article;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Venbhas\Article\Model\Config;
use Venbhas\Article\Model\ResourceModel\Article\CollectionFactory;
use Venbhas\Article\Model\ResourceModel\Category\CollectionFactory as categoryCollectionFactory;

/**
 * Block for article list page.
 */
class ListBlock extends Template
{
    /** @var CollectionFactory */
    private $collectionFactory;

    /** @var categoryCollectionFactory */
    private $categoryCollectionFactory;

    /** @var Config */
    private $config;

    /** @var StoreManagerInterface */
    private $storeManager;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param CollectionFactory $collectionFactory
     * @param categoryCollectionFactory $categoryCollectionFactory
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        categoryCollectionFactory $categoryCollectionFactory,
        Config $config,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->config = $config;
        $this->storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    /**
     * Get article collection for list.
     *
     * @return \Magento\Framework\Data\Collection\AbstractDb
     */
    public function getArticles()
    {
        if (!$this->hasData('articles')) {
            $page  = (int) $this->getRequest()->getParam('p', 1);
            $storeId = (int) $this->storeManager->getStore()->getId();
            $limit = (int) $this->getRequest()->getParam('limit', $this->config->getArticlesPerPage($storeId));
            $order = $this->getCurrentSortOrder();

            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('is_active', 1);
            $collection->addFieldToFilter('status', 1);
            $sort = $this->config->getSortOrderFieldAndDirection($order);
            $collection->setOrder($sort['field'], $sort['direction']);

            $collection->setCurPage($page);
            $collection->setPageSize($limit);

            $this->setData('articles', $collection);
        }
        return $this->getData('articles');
    }

    /**
     * Get current sort order from request or config.
     *
     * @return string
     */
    public function getCurrentSortOrder(): string
    {
        $requestOrder = $this->getRequest()->getParam('order', '');
        $valid = array_keys($this->config->getSortOptionsForFrontend());
        if ($requestOrder !== '' && in_array($requestOrder, $valid, true)) {
            return $requestOrder;
        }
        $storeId = (int) $this->storeManager->getStore()->getId();
        return $this->config->getDefaultSortOrder($storeId);
    }

    /**
     * Get sort options for frontend.
     *
     * @return array
     */
    public function getSortOptions(): array
    {
        return $this->config->getSortOptionsForFrontend();
    }

    /**
     * URL for sort option (preserves path and pagination params).
     *
     * @param string $order
     * @return string
     */
    public function getSortUrl(string $order): string
    {
        $params = ['order' => $order];
        $p = $this->getRequest()->getParam('p');
        if ($p !== null && (int) $p > 1) {
            $params['p'] = (int) $p;
        }
        return $this->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true, '_query' => $params]);
    }

    /**
     * Get category collection for sidebar.
     *
     * @return \Magento\Framework\Data\Collection\AbstractDb
     */
    public function getCategories()
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->addFieldToFilter('status', 1);
        $collection->setOrder('updated_at', 'desc');
        return $collection;
    }

    /**
     * Article detail URL (path from store config: Article List URL Key + / + url_key).
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

    /**
     * Prepare layout and add pager.
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $collection = $this->getArticles();
        $pager = $this->getLayout()->createBlock(\Magento\Theme\Block\Html\Pager::class, 'articles_list.pager');
        $pager->setLimit($collection->getPageSize());
        $pager->setCollection($collection);
        $pager->setShowPerPage(false);
        $this->setChild('pager', $pager);
        return parent::_prepareLayout();
    }

    /**
     * Get pager HTML.
     *
     * @return string
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }
}
