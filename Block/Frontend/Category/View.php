<?php
declare(strict_types=1);

namespace Venbhas\Blog\Block\Frontend\Category;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Venbhas\Blog\Block\Frontend\Article\ToolbarAwareInterface;
use Venbhas\Blog\Block\Frontend\Article\ToolbarAwareTrait;
use Venbhas\Blog\Block\Frontend\ModuleEnabledTrait;
use Venbhas\Blog\Model\Category;
use Venbhas\Blog\Model\Config;
use Venbhas\Blog\Model\Content\HtmlFilter;
use Venbhas\Blog\Model\ResourceModel\Article\CollectionFactory as ArticleCollectionFactory;
use Venbhas\Blog\Model\ResourceModel\Category\RelatedProducts;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Block for category view page.
 */
class View extends Template implements ToolbarAwareInterface
{
    use ModuleEnabledTrait;
    use ToolbarAwareTrait;
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

    /** @var HtmlFilter */
    private $htmlFilter;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param ArticleCollectionFactory $articleCollectionFactory
     * @param RelatedProducts $relatedProducts
     * @param Registry $registry
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param HtmlFilter $htmlFilter
     * @param array $data
     */
    public function __construct(
        Context $context,
        ArticleCollectionFactory $articleCollectionFactory,
        RelatedProducts $relatedProducts,
        Registry $registry,
        StoreManagerInterface $storeManager,
        Config $config,
        HtmlFilter $htmlFilter,
        array $data = []
    ) {
        $this->articleCollectionFactory = $articleCollectionFactory;
        $this->relatedProducts = $relatedProducts;
        $this->registry = $registry;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->htmlFilter = $htmlFilter;
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
     * Category description with CMS widgets and Page Builder markup rendered.
     */
    public function getFilteredDescription(): string
    {
        $category = $this->getCategory();
        if (!$category) {
            return '';
        }

        return $this->htmlFilter->filter($category->getDescription());
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
        $limit = $this->getPageLimit();

        $collection = $this->articleCollectionFactory->create();
        $collection->addFieldToFilter('status', 1);
        $collection->join(
            ['rel' => 'venbhas_article_category_relation'],
            'main_table.article_id = rel.article_id AND rel.category_id = ' . (int) $category->getId(),
            []
        );
        $this->applyToolbarSort($collection);
        $collection->setCurPage($page);
        $collection->setPageSize($limit);
        $this->setData('articles', $collection);
        return $collection;
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
     * Return the article collection for the category toolbar.
     *
     * @return \Magento\Framework\Data\Collection\AbstractDb|array
     */
    protected function getToolbarCollection()
    {
        return $this->getArticles();
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
