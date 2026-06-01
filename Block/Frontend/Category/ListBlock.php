<?php
declare(strict_types=1);

namespace Venbhas\Blog\Block\Frontend\Category;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Venbhas\Blog\Block\Frontend\Article\ToolbarAwareInterface;
use Venbhas\Blog\Block\Frontend\Article\ToolbarAwareTrait;
use Venbhas\Blog\Block\Frontend\ModuleEnabledTrait;
use Venbhas\Blog\Model\Config;
use Venbhas\Blog\Model\ResourceModel\Category\CollectionFactory;

/**
 * Block for category list page.
 */
class ListBlock extends Template implements ToolbarAwareInterface
{
    use ModuleEnabledTrait;
    use ToolbarAwareTrait;

    /** @var CollectionFactory */
    private $collectionFactory;

    /** @var Config */
    private $config;

    /** @var StoreManagerInterface */
    private $storeManager;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param CollectionFactory $collectionFactory
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        Config $config,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->config = $config;
        $this->storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    /**
     * Get paginated category collection.
     *
     * @return \Magento\Framework\Data\Collection\AbstractDb
     */
    public function getCategories()
    {
        if (!$this->hasData('categories')) {
            $page = (int) $this->getRequest()->getParam('p', 1);
            $limit = $this->getPageLimit();

            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('status', 1);
            $this->applyToolbarSort($collection);
            $collection->setCurPage($page);
            $collection->setPageSize($limit);

            $this->setData('categories', $collection);
        }

        return $this->getData('categories');
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
     * Category view URL (path from store config: Article List URL Key + /category/ + url_key).
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

    /**
     * Apply category list sort order to the collection.
     *
     * @param AbstractCollection $collection
     * @return void
     */
    protected function applyToolbarSort(AbstractCollection $collection): void
    {
        $sort = $this->config->getCategorySortOrderFieldAndDirection($this->getCurrentSortOrder());
        $collection->setOrder($sort['field'], $sort['direction']);
    }

    /**
     * Return the category collection for the list toolbar.
     *
     * @return \Magento\Framework\Data\Collection\AbstractDb
     */
    protected function getToolbarCollection()
    {
        return $this->getCategories();
    }

    /**
     * Prepare layout: set page and meta title for category list page.
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $title = (string) __('Categories');
        $this->pageConfig->getTitle()->set($title);
        $this->pageConfig->setMetaTitle($title);

        return parent::_prepareLayout();
    }
}
