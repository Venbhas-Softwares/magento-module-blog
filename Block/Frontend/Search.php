<?php
declare(strict_types=1);

namespace Venbhas\Article\Block\Frontend;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Venbhas\Article\Model\Config;

class Search extends Template
{
    /** @var Config */
    private $config;

    /** @var StoreManagerInterface */
    private $storeManager;

    public function __construct(
        Context $context,
        Config $config,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->config = $config;
        $this->storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    /**
     * URL for article/category search results (GET with q=).
     */
    public function getSearchUrl(): string
    {
        $storeId = (int) $this->storeManager->getStore()->getId();
        $basePath = trim($this->config->getArticleListRoute($storeId), '/');
        return $this->getUrl('', ['_direct' => $basePath . '/search']);
    }
}
