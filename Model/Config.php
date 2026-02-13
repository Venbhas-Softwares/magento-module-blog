<?php
declare(strict_types=1);

namespace Venbhas\Article\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const XML_PATH_ENABLED = 'venbhas_article/general/enabled';
    private const XML_PATH_COMMENTS_ENABLED = 'venbhas_article/general/comments_enabled';
    private const XML_PATH_ARTICLE_LIST_ROUTE = 'venbhas_article/general/article_list_route';
    private const XML_PATH_CATEGORY_LIST_ROUTE = 'venbhas_article/general/category_list_route';
    private const XML_PATH_META_ROBOTS_CATEGORY = 'venbhas_article/meta_robots/category';
    private const XML_PATH_META_ROBOTS_POST = 'venbhas_article/meta_robots/post';

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function isModuleEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isCommentsEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_COMMENTS_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getArticleListRoute(?int $storeId = null): string
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_ARTICLE_LIST_ROUTE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $value !== null && $value !== '' ? trim((string) $value) : 'articles';
    }

    public function getCategoryListRoute(?int $storeId = null): string
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_CATEGORY_LIST_ROUTE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $value !== null && $value !== '' ? trim((string) $value) : 'categories';
    }

    public function getCategoryMetaRobots(?int $storeId = null): string
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_META_ROBOTS_CATEGORY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $value !== null ? (string) $value : 'INDEX,FOLLOW';
    }

    public function getPostMetaRobots(?int $storeId = null): string
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_META_ROBOTS_POST,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $value !== null ? (string) $value : 'INDEX,FOLLOW';
    }
}
