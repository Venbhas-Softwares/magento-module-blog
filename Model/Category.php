<?php
declare(strict_types=1);

namespace Venbhas\Blog\Model;

use Magento\Framework\Model\AbstractModel;
use Venbhas\Blog\Model\ResourceModel\Category as CategoryResource;

/**
 * Category model.
 */
class Category extends AbstractModel
{
    public const CACHE_TAG = 'venbhas_blog_category';

    /** @var string */
    protected $_cacheTag = self::CACHE_TAG;

    /** @var string */
    protected $_eventPrefix = 'venbhas_blog_category';

    /**
     * Initialize resource model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(CategoryResource::class);
    }
}
