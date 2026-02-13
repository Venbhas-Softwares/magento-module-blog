<?php
declare(strict_types=1);

namespace Venbhas\Article\Model;

use Magento\Framework\Model\AbstractModel;
use Venbhas\Article\Model\ResourceModel\Article as ArticleResource;

class Article extends AbstractModel
{
    const CACHE_TAG = 'venbhas_article';
    protected $_cacheTag = self::CACHE_TAG;
    protected $_eventPrefix = 'venbhas_article';

    protected function _construct()
    {
        $this->_init(ArticleResource::class);
    }
}
