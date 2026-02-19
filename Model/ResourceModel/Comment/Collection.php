<?php
declare(strict_types=1);

namespace Venbhas\Article\Model\ResourceModel\Comment;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Venbhas\Article\Model\Comment;
use Venbhas\Article\Model\ResourceModel\Comment as CommentResource;

/**
 * Comment collection.
 */
class Collection extends AbstractCollection
{
    /** @var string */
    protected $_idFieldName = 'comment_id';

    /**
     * Initialize Comment collection.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Comment::class, CommentResource::class);
    }
}
