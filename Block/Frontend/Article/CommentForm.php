<?php
declare(strict_types=1);

namespace Venbhas\Article\Block\Frontend\Article;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Venbhas\Article\Model\Article;
use Venbhas\Article\Model\Config;

class CommentForm extends Template
{
    /** @var Config */
    private $config;

    /** @var Registry */
    private $registry;

    /**
     * @param Context $context
     * @param Config $config
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        Registry $registry,
        array $data = []
    ) {
        $this->config = $config;
        $this->registry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Get current article from registry.
     *
     * @return Article|null
     */
    /**
     * Get current article from registry.
     *
     * @return Article|null
     */
    public function getArticle(): ?Article
    {
        return $this->registry->registry('current_article');
    }

    /**
     * Check if comments are enabled.
     *
     * @return bool
     */
    public function isCommentsEnabled(): bool
    {
        $storeId = (int) $this->_storeManager->getStore()->getId();
        return $this->config->isCommentsEnabled($storeId);
    }

    /**
     * Get comment post action URL.
     *
     * @return string
     */
    public function getPostActionUrl(): string
    {
        return $this->getUrl('article/comment/post');
    }

    /**
     * Include comments_enabled and store in cache key so section hides/shows when config changes.
     */
    public function getCacheKeyInfo()
    {
        $storeId = (int) $this->_storeManager->getStore()->getId();
        $key = parent::getCacheKeyInfo() ?? [];
        $key['comments_enabled'] = $this->config->isCommentsEnabled($storeId) ? '1' : '0';
        return $key;
    }
}
