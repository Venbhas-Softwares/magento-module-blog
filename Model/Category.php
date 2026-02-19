<?php
declare(strict_types=1);

namespace Venbhas\Article\Model;

use Magento\Framework\Model\AbstractModel;
use Venbhas\Article\Model\ResourceModel\Category as CategoryResource;

/**
 * Category model.
 */
class Category extends AbstractModel
{
    public const CACHE_TAG = 'venbhas_article_category';

    /** @var string */
    protected $_cacheTag = self::CACHE_TAG;

    /** @var string */
    protected $_eventPrefix = 'venbhas_article_category';

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
