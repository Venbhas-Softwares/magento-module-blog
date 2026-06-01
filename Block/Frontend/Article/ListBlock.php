<?php
declare(strict_types=1);

namespace Venbhas\Blog\Block\Frontend\Article;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Venbhas\Blog\Model\Config;
use Venbhas\Blog\Model\ResourceModel\Article\CollectionFactory;
use Venbhas\Blog\Block\Frontend\ModuleEnabledTrait;
use Venbhas\Blog\Model\ResourceModel\Category\CollectionFactory as categoryCollectionFactory;

/**
 * Block for article list page.
 */
class ListBlock extends Template implements ToolbarAwareInterface
{
    use ModuleEnabledTrait;
    use ToolbarAwareTrait;
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
            $limit = $this->getPageLimit();

            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('status', 1);
            $this->applyToolbarSort($collection);

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
     * @param \Venbhas\Blog\Model\Category $category
     * @return string
     */
    public function getCategoryUrl($category): string
    {
        $storeId = (int) $this->storeManager->getStore()->getId();
        $basePath = trim($this->config->getArticleListRoute($storeId), '/');
        return $this->getUrl('', ['_direct' => $basePath . '/category/' . $category->getUrlKey()]);
    }

    /**
     * Return the article collection for the list toolbar.
     *
     * @return \Magento\Framework\Data\Collection\AbstractDb
     */
    protected function getToolbarCollection()
    {
        return $this->getArticles();
    }
}
