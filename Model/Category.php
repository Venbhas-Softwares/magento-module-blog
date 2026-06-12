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

    /** Virtual root node id for admin category tree */
    public const TREE_ROOT_ID = 0;

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

    /**
     * Get path ids including this category.
     *
     * @return int[]
     */
    public function getPathIds(): array
    {
        $path = trim((string) $this->getData('path'));
        if ($path === '') {
            return $this->getId() ? [(int) $this->getId()] : [];
        }

        return array_values(array_filter(array_map('intval', explode('/', $path))));
    }
}
