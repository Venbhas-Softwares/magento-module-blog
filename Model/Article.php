<?php
declare(strict_types=1);

namespace Venbhas\Article\Model;

use Magento\Framework\Model\AbstractModel;
use Venbhas\Article\Model\ResourceModel\Article as ArticleResource;

/**
 * Article model.
 */
class Article extends AbstractModel
{
    public const CACHE_TAG = 'venbhas_article';

    /** @var string */
    protected $_cacheTag = self::CACHE_TAG;

    /** @var string */
    protected $_eventPrefix = 'venbhas_article';

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
