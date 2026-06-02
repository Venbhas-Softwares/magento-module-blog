<?php
declare(strict_types=1);

namespace Venbhas\Blog\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Article module configuration.
 */
class Config
{
    private const XML_PATH_ENABLED = 'venbhas_blog/general/enabled';
    private const XML_PATH_COMMENTS_ENABLED = 'venbhas_blog/general/comments_enabled';
    private const XML_PATH_ARTICLE_LIST_ROUTE = 'venbhas_blog/general/article_list_route';
    private const XML_PATH_CATEGORY_LIST_ROUTE = 'venbhas_blog/general/category_list_route';
    private const XML_PATH_ARTICLES_PER_PAGE = 'venbhas_blog/general/articles_per_page';
    private const XML_PATH_DEFAULT_SORT_ORDER = 'venbhas_blog/general/default_sort_order';
    private const XML_PATH_RELATED_PRODUCTS_LIMIT = 'venbhas_blog/general/related_products_limit';
    private const XML_PATH_META_ROBOTS_CATEGORY = 'venbhas_blog/meta_robots/category';
    private const XML_PATH_META_ROBOTS_POST = 'venbhas_blog/meta_robots/post';

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /**
     * Constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Check if module is enabled.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isModuleEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Whether the module is enabled at default (global) scope — used for admin UI visibility.
     *
     * @return bool
     */
    public function isModuleEnabledForAdmin(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

    /**
     * Alias for {@see isModuleEnabled()} — use in plugins via ModuleEnabledGuard when possible.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return $this->isModuleEnabled($storeId);
    }

    /**
     * Check if comments are enabled.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isCommentsEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_COMMENTS_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get article list route.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getArticleListRoute(?int $storeId = null): string
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_ARTICLE_LIST_ROUTE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $value !== null && $value !== '' ? trim((string) $value) : 'articles';
    }

    /**
     * Get category list route.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getCategoryListRoute(?int $storeId = null): string
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_CATEGORY_LIST_ROUTE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $value !== null && $value !== '' ? trim((string) $value) : 'categories';
    }

    /**
     * Get articles per page.
     *
     * @param int|null $storeId
     * @return int
     */
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
     * Page size options for the article list limiter (value => label value).
     *
     * @param int|null $storeId
     * @return array<int, int>
     */
    public function getAvailablePageLimits(?int $storeId = null): array
    {
        $default = $this->getArticlesPerPage($storeId);
        $values = array_unique(array_filter([$default, 10, 20, 50], static function ($value) {
            return (int) $value > 0;
        }));
        sort($values, SORT_NUMERIC);

        $limits = [];
        foreach ($values as $value) {
            $limits[(int) $value] = (int) $value;
        }

        return $limits;
    }

    /**
     * Default sort order for article/category lists. One of: new_to_old, old_to_new, a_to_z, z_to_a
     *
     * @param int|null $storeId
     * @return string
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

    /**
     * Get related products limit.
     *
     * @param int|null $storeId
     * @return int
     */
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
     * Sort options for frontend dropdown [value => label].
     *
     * @return array
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
     *
     * @param string $order Sort key (new_to_old, old_to_new, a_to_z, z_to_a)
     * @return array{field: string, direction: string}
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

    /**
     * Return [field, direction] for category collection setOrder.
     *
     * @param string $order Sort key (new_to_old, old_to_new, a_to_z, z_to_a)
     * @return array{field: string, direction: string}
     */
    public function getCategorySortOrderFieldAndDirection(string $order): array
    {
        switch ($order) {
            case 'old_to_new':
                return ['field' => 'updated_at', 'direction' => 'ASC'];
            case 'a_to_z':
                return ['field' => 'name', 'direction' => 'ASC'];
            case 'z_to_a':
                return ['field' => 'name', 'direction' => 'DESC'];
            case 'new_to_old':
            default:
                return ['field' => 'updated_at', 'direction' => 'DESC'];
        }
    }

    /**
     * Get category meta robots value.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getCategoryMetaRobots(?int $storeId = null): string
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_META_ROBOTS_CATEGORY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $value !== null ? (string) $value : 'INDEX,FOLLOW';
    }

    /**
     * Get post meta robots value.
     *
     * @param int|null $storeId
     * @return string
     */
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
