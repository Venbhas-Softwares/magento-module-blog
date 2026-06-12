<?php
declare(strict_types=1);

namespace Venbhas\Blog\Model;

use Magento\Framework\Model\AbstractModel;
use Venbhas\Blog\Model\ResourceModel\Comment as CommentResource;

/**
 * Comment model.
 */
class Comment extends AbstractModel
{
    public const STATUS_PENDING = 0;
    public const STATUS_APPROVED = 1;
    public const STATUS_REJECTED = 2;

    public const CACHE_TAG = 'venbhas_blog_comment';

    /** @var string */
    protected $_cacheTag = self::CACHE_TAG;

    /** @var string */
    protected $_eventPrefix = 'venbhas_blog_comment';

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
