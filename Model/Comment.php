<?php
declare(strict_types=1);

namespace Venbhas\Article\Model;

use Magento\Framework\Model\AbstractModel;
use Venbhas\Article\Model\ResourceModel\Comment as CommentResource;

/**
 * Comment model.
 */
class Comment extends AbstractModel
{
    public const STATUS_PENDING = 0;
    public const STATUS_APPROVED = 1;
    public const STATUS_REJECTED = 2;

    public const CACHE_TAG = 'venbhas_article_comment';

    /** @var string */
    protected $_cacheTag = self::CACHE_TAG;

    /** @var string */
    protected $_eventPrefix = 'venbhas_article_comment';

    /**
     * Initialize resource model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(CommentResource::class);
    }
}
