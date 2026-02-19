<?php
declare(strict_types=1);

namespace Venbhas\Article\Block\Frontend\Category;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Venbhas\Article\Model\Category;
use Venbhas\Article\Model\Config;
use Venbhas\Article\Model\ResourceModel\Article\CollectionFactory as ArticleCollectionFactory;
use Venbhas\Article\Model\ResourceModel\Category\RelatedProducts;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Block for category view page.
 */
class View extends Template
{
    /** @var ArticleCollectionFactory */
    private $articleCollectionFactory;

    /** @var RelatedProducts */
    private $relatedProducts;

    /** @var Registry */
    private $registry;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var Config */
    private $config;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param ArticleCollectionFactory $articleCollectionFactory
     * @param RelatedProducts $relatedProducts
     * @param Registry $registry
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Context $context,
        ArticleCollectionFactory $articleCollectionFactory,
        RelatedProducts $relatedProducts,
        Registry $registry,
        StoreManagerInterface $storeManager,
        Config $config,
        array $data = []
    ) {
        $this->articleCollectionFactory = $articleCollectionFactory;
        $this->relatedProducts = $relatedProducts;
        $this->registry = $registry;
        $this->storeManager = $storeManager;
        $this->config = $config;
        parent::__construct($context, $data);
    }

    /**
     * Get current category from layout data or registry.
     *
     * @return Category|null
     */
    public function getCategory(): ?Category
    {
        return $this->_data['category'] ?? $this->registry->registry('current_article_category');
    }

    /**
     * Get related product IDs for the current category.
     *
     * @return int[]
     */
    public function getRelatedProductIds(): array
    {
        $category = $this->getCategory();
        return $category ? $this->relatedProducts->getRelatedProductIds((int) $category->getId()) : [];
    }

    /**
     * Get articles collection for the current category (paginated).
     *
     * @return \Magento\Framework\Data\Collection\AbstractDb|array
     */
    public function getArticles()
    {
        $category = $this->getCategory();
        if (!$category) {
            return [];
        }
        if ($this->hasData('articles')) {
            return $this->getData('articles');
        }
        $page  = (int) $this->getRequest()->getParam('p', 1);
        $storeId = (int) $this->storeManager->getStore()->getId();
        $limit = (int) $this->getRequest()->getParam('limit', $this->config->getArticlesPerPage($storeId));
        $order = $this->getCurrentSortOrder();

        $collection = $this->articleCollectionFactory->create();
        $collection->addFieldToFilter('is_active', 1);
        $collection->join(
            ['rel' => 'venbhas_article_category_relation'],
            'main_table.article_id = rel.article_id AND rel.category_id = ' . (int) $category->getId(),
            []
        );
        $sort = $this->config->getSortOrderFieldAndDirection($order);
        $collection->setOrder($sort['field'], $sort['direction']);
        $collection->setCurPage($page);
        $collection->setPageSize($limit);
        $this->setData('articles', $collection);
        return $collection;
    }

    /**
     * Prepare layout: add pager for articles.
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        if ($this->getCategory()) {
            $collection = $this->getArticles();
            if ($collection instanceof \Magento\Framework\Data\Collection\AbstractDb) {
                $pager = $this->getLayout()->createBlock(
                    \Magento\Theme\Block\Html\Pager::class,
                    'article_category.pager'
                );
                $pager->setLimit($collection->getPageSize());
                $pager->setCollection($collection);
                $pager->setShowPerPage(false);
                $this->setChild('pager', $pager);
            }
        }
        return parent::_prepareLayout();
    }

    /**
     * Get pager HTML.
     *
     * @return string
     */
    public function getPagerHtml(): string
    {
        return (string) $this->getChildHtml('pager');
    }

    /**
     * Get current sort order from request or config default.
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
     * Get sort options for frontend (order => label).
     *
     * @return array
     */
    public function getSortOptions(): array
    {
        return $this->config->getSortOptionsForFrontend();
    }

    /**
     * Get URL for category page with given sort order.
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
     * Format datetime as human-readable time ago string.
     *
     * @param string $datetime
     * @return string
     */
    public function getTimeAgo($datetime)
    {
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return $diff . ' seconds';
        } elseif ($diff < 3600) {
            return floor($diff / 60) . ' minutes';
        } elseif ($diff < 86400) {
            return floor($diff / 3600) . ' hours';
        } elseif ($diff < 2592000) {
            return floor($diff / 86400) . ' days';
        } elseif ($diff < 31536000) {
            return floor($diff / 2592000) . ' months';
        } else {
            return floor($diff / 31536000) . ' years';
        }
    }

    /**
     * Get full media URL for image path.
     *
     * @param string|null $image
     * @return string|false
     */
    public function getImageUrl($image)
    {
        if (!$image) {
            return false;
        }
            
        return $this->storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ) . ltrim($image, '/');
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
}
