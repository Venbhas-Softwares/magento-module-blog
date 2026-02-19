<?php
declare(strict_types=1);

namespace Venbhas\Article\Model\ResourceModel\Article;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Venbhas\Article\Model\Article;
use Venbhas\Article\Model\ResourceModel\Article as ArticleResource;

/**
 * Article collection.
 */
class Collection extends AbstractCollection
{
    /** @var string */
    protected $_idFieldName = 'article_id';

    /**
     * Initialize Article collection.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Article::class, ArticleResource::class);
    }
}
