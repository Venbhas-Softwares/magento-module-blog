<?php
declare(strict_types=1);

namespace Venbhas\Article\Model\ResourceModel\Article;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Venbhas\Article\Model\Article;
use Venbhas\Article\Model\ResourceModel\Article as ArticleResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'article_id';

    protected function _construct()
    {
        $this->_init(Article::class, ArticleResource::class);
    }
}
