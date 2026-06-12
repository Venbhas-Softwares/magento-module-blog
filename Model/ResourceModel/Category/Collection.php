<?php
declare(strict_types=1);

namespace Venbhas\Blog\Model\ResourceModel\Category;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Venbhas\Blog\Model\Category;
use Venbhas\Blog\Model\ResourceModel\Category as CategoryResource;

/**
 * Category collection.
 */
class Collection extends AbstractCollection
{
    /** @var string */
    protected $_idFieldName = 'category_id';

    /**
     * Initialize Category collection.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Category::class, CategoryResource::class);
    }
}
