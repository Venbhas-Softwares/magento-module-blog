<?php
declare(strict_types=1);

namespace Venbhas\Article\Model\ResourceModel\Category;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Venbhas\Article\Model\Category;
use Venbhas\Article\Model\ResourceModel\Category as CategoryResource;

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
