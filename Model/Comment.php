<?php
declare(strict_types=1);

namespace Venbhas\Article\Model;

use Magento\Framework\Model\AbstractModel;
use Venbhas\Article\Model\ResourceModel\Comment as CommentResource;

class Comment extends AbstractModel
{
    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;
    const STATUS_REJECTED = 2;

    const CACHE_TAG = 'venbhas_article_comment';
    protected $_cacheTag = self::CACHE_TAG;
    protected $_eventPrefix = 'venbhas_article_comment';

    protected function _construct()
    {
        $this->_init(CommentResource::class);
    }
}
