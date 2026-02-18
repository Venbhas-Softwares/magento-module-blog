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

    public function getArticle(): ?Article
    {
        return $this->registry->registry('current_article');
    }

    public function isCommentsEnabled(): bool
    {
        $storeId = (int) $this->_storeManager->getStore()->getId();
        return $this->config->isCommentsEnabled($storeId);
    }

    public function getPostActionUrl(): string
    {
        return $this->getUrl('article/comment/post');
    }
}
