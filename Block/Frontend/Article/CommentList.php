<?php
declare(strict_types=1);

namespace Venbhas\Article\Block\Frontend\Article;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Venbhas\Article\Model\Comment;
use Venbhas\Article\Model\Config;
use Venbhas\Article\Model\ResourceModel\Comment\Collection;
use Venbhas\Article\Model\ResourceModel\Comment\CollectionFactory as CommentCollectionFactory;

class CommentList extends Template
{
    /** @var Config */
    private $config;

    /** @var Registry */
    private $registry;

    /** @var CommentCollectionFactory */
    private $commentCollectionFactory;

    /** @var Comment[]|null */
    private $comments;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param Config $config
     * @param Registry $registry
     * @param CommentCollectionFactory $commentCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        Registry $registry,
        CommentCollectionFactory $commentCollectionFactory,
        array $data = []
    ) {
        $this->config = $config;
        $this->registry = $registry;
        $this->commentCollectionFactory = $commentCollectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * Get approved comments for the current article.
     *
     * @return Comment[]
     */
    public function getApprovedComments()
    {
        if ($this->comments !== null) {
            return $this->comments;
        }
        $article = $this->registry->registry('current_article');
        if (!$article || !$article->getId()) {
            $this->comments = [];
            return $this->comments;
        }
        /** @var Collection $collection */
        $collection = $this->commentCollectionFactory->create();
        $collection->addFieldToFilter('article_id', (int) $article->getId())
            ->addFieldToFilter('status', Comment::STATUS_APPROVED)
            ->setOrder('created_at', 'asc');
        $this->comments = $collection->getItems();
        return $this->comments;
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
     * Format comment date for display.
     *
     * @param string|null $date
     * @return string
     */
    public function formatCommentDate(?string $date): string
    {
        if ($date === null || $date === '') {
            return '';
        }
        return $this->_localeDate->formatDateTime(
            $date,
            \IntlDateFormatter::MEDIUM,
            \IntlDateFormatter::SHORT
        );
    }

    /**
     * Include comments_enabled and store in cache key so section hides/shows when config changes.
     *
     * @return array
     */
    public function getCacheKeyInfo()
    {
        $storeId = (int) $this->_storeManager->getStore()->getId();
        $key = parent::getCacheKeyInfo() ?? [];
        $key['comments_enabled'] = $this->config->isCommentsEnabled($storeId) ? '1' : '0';
        return $key;
    }
}
