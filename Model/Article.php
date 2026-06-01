<?php
declare(strict_types=1);

namespace Venbhas\Blog\Model;

use Magento\Framework\Model\AbstractModel;
use Venbhas\Blog\Model\ResourceModel\Article as ArticleResource;

/**
 * Article model.
 */
class Article extends AbstractModel
{
    public const CACHE_TAG = 'venbhas_blog';

    /** @var string */
    protected $_cacheTag = self::CACHE_TAG;

    /** @var string */
    protected $_eventPrefix = 'venbhas_blog';

    /**
     * Initialize resource model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ArticleResource::class);
    }
}
