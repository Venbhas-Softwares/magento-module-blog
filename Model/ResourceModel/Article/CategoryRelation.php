<?php
declare(strict_types=1);

namespace Venbhas\Blog\Model\ResourceModel\Article;

use Magento\Framework\App\ResourceConnection;
use Venbhas\Blog\Model\ResourceModel\RelatedArticlesResourceInterface;

/**
 * Article-Category relation resource.
 */
class CategoryRelation implements RelatedArticlesResourceInterface
{
    private const TABLE = 'venbhas_article_category_relation';

    /** @var ResourceConnection */
    private $resource;

    /**
     * @param ResourceConnection $resource
     */
    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    /**
     * Get category id by article id.
     *
     * @param int $articleId
     * @return int|null
     */
    public function getCategoryIdByArticleId(int $articleId): ?int
    {
        $categoryIds = $this->getCategoryIdsByArticleId($articleId);

        return $categoryIds !== [] ? $categoryIds[0] : null;
    }

    /**
     * Get assigned category ids for an article.
     *
     * @param int $articleId
     * @return int[]
     */
    public function getCategoryIdsByArticleId(int $articleId): array
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from($this->resource->getTableName(self::TABLE), 'category_id')
            ->where('article_id = ?', $articleId)
            ->order('category_id ASC');
        $values = $connection->fetchCol($select);

        return array_values(array_map('intval', $values ?: []));
    }

    /**
     * Save article-category relations.
     *
     * @param int $articleId
     * @param int[] $categoryIds
     * @return void
     */
    public function saveArticleCategories(int $articleId, array $categoryIds): void
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName(self::TABLE);
        $connection->delete($table, ['article_id = ?' => $articleId]);

        $categoryIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));
        foreach ($categoryIds as $categoryId) {
            if ($categoryId > 0) {
                $connection->insert($table, [
                    'article_id' => $articleId,
                    'category_id' => $categoryId,
                ]);
            }
        }
    }

    /**
     * Save a single article-category relation (backward compatible).
     *
     * @param int $articleId
     * @param int|null $categoryId
     * @return void
     */
    public function saveArticleCategory(int $articleId, ?int $categoryId): void
    {
        $this->saveArticleCategories(
            $articleId,
            $categoryId !== null && $categoryId > 0 ? [$categoryId] : []
        );
    }

    /**
     * Get article ids by category id.
     *
     * @param int $categoryId
     * @return array
     */
    public function getArticleIdsByCategoryId(int $categoryId): array
    {
        return $this->getRelatedArticleIds($categoryId);
    }

    /**
     * @inheritdoc
     */
    public function getRelatedArticleIds(int $entityId): array
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from($this->resource->getTableName(self::TABLE), 'article_id')
            ->where('category_id = ?', $entityId)
            ->order('article_id ASC');
        return array_map('intval', $connection->fetchCol($select) ?: []);
    }

    /**
     * Save category-articles relations.
     *
     * @param int $categoryId
     * @param array $articleIds
     * @return void
     */
    public function saveCategoryArticles(int $categoryId, array $articleIds): void
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName(self::TABLE);
        $connection->delete($table, ['category_id = ?' => $categoryId]);
        foreach ($articleIds as $articleId) {
            if ((int) $articleId > 0) {
                $connection->insert($table, [
                    'article_id' => (int) $articleId,
                    'category_id' => $categoryId,
                ]);
            }
        }
    }
}
