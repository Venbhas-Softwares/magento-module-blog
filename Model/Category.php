<?php
declare(strict_types=1);

namespace Venbhas\Article\Model;

use Magento\Framework\Model\AbstractModel;
use Venbhas\Article\Model\ResourceModel\Category as CategoryResource;

class Category extends AbstractModel
{
    const CACHE_TAG = 'venbhas_article_category';
    protected $_cacheTag = self::CACHE_TAG;
    protected $_eventPrefix = 'venbhas_article_category';

    protected function _construct()
    {
        $this->_init(CategoryResource::class);
    }
}
