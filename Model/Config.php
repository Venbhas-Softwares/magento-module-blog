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
    private const XML_PATH_ARTICLES_PER_PAGE = 'venbhas_article/general/articles_per_page';
    private const XML_PATH_DEFAULT_SORT_ORDER = 'venbhas_article/general/default_sort_order';
    private const XML_PATH_RELATED_PRODUCTS_LIMIT = 'venbhas_article/general/related_products_limit';
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

    public function getArticlesPerPage(?int $storeId = null): int
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_ARTICLES_PER_PAGE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $int = $value !== null && $value !== '' ? (int) $value : 10;
        return $int > 0 ? $int : 10;
    }

    /**
     * Default sort order for article/category lists. One of: new_to_old, old_to_new, a_to_z, z_to_a
     */
    public function getDefaultSortOrder(?int $storeId = null): string
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_DEFAULT_SORT_ORDER,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $valid = ['new_to_old', 'old_to_new', 'a_to_z', 'z_to_a'];
        return $value && in_array($value, $valid, true) ? $value : 'new_to_old';
    }

    public function getRelatedProductsLimit(?int $storeId = null): int
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_RELATED_PRODUCTS_LIMIT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $int = $value !== null && $value !== '' ? (int) $value : 10;
        return $int > 0 ? $int : 10;
    }

    /**
     * Sort options for frontend dropdown [value => label]
     */
    public function getSortOptionsForFrontend(): array
    {
        return [
            'new_to_old' => (string) __('New to Old'),
            'old_to_new' => (string) __('Old to New'),
            'a_to_z' => (string) __('A to Z'),
            'z_to_a' => (string) __('Z to A'),
        ];
    }

    /**
     * Return [field, direction] for collection setOrder. Field is main_table column.
     */
    public function getSortOrderFieldAndDirection(string $order): array
    {
        switch ($order) {
            case 'old_to_new':
                return ['field' => 'updated_at', 'direction' => 'ASC'];
            case 'a_to_z':
                return ['field' => 'title', 'direction' => 'ASC'];
            case 'z_to_a':
                return ['field' => 'title', 'direction' => 'DESC'];
            case 'new_to_old':
            default:
                return ['field' => 'updated_at', 'direction' => 'DESC'];
        }
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
