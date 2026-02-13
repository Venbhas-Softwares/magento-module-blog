<?php
declare(strict_types=1);

namespace Venbhas\Article\Model\ResourceModel\Category;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Venbhas\Article\Model\Category;
use Venbhas\Article\Model\ResourceModel\Category as CategoryResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'category_id';

    protected function _construct()
    {
        $this->_init(Category::class, CategoryResource::class);
    }
}
