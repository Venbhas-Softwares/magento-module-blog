<?php
declare(strict_types=1);

namespace Venbhas\Article\Model\ResourceModel\Comment;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Venbhas\Article\Model\Comment;
use Venbhas\Article\Model\ResourceModel\Comment as CommentResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'comment_id';

    protected function _construct()
    {
        $this->_init(Comment::class, CommentResource::class);
    }
}
