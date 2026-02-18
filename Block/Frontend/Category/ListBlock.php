<?php
declare(strict_types=1);

namespace Venbhas\Article\Block\Frontend\Category;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Venbhas\Article\Model\Config;
use Venbhas\Article\Model\ResourceModel\Category\CollectionFactory;

class ListBlock extends Template
{
    /** @var CollectionFactory */
    private $collectionFactory;

    /** @var Config */
    private $config;

    /** @var StoreManagerInterface */
    private $storeManager;

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

    public function getCategories()
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('status', 1);
        $collection->setOrder('name', 'asc');
        return $collection;
    }

    /**
     * Category view URL (path from store config: Article List URL Key + /category/ + url_key).
     */
    public function getCategoryUrl(string $urlKey): string
    {
        $storeId = (int) $this->storeManager->getStore()->getId();
        $basePath = trim($this->config->getArticleListRoute($storeId), '/');
        return $this->getUrl('', ['_direct' => $basePath . '/category/' . $urlKey]);
    }
}
