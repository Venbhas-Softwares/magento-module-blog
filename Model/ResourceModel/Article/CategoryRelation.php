<?php
declare(strict_types=1);

namespace Venbhas\Article\Model\ResourceModel\Article;

use Magento\Framework\App\ResourceConnection;

/**
 * Article-Category relation resource.
 */
class CategoryRelation
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
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from($this->resource->getTableName(self::TABLE), 'category_id')
            ->where('article_id = ?', $articleId)
            ->limit(1);
        $value = $connection->fetchOne($select);
        return $value !== false ? (int) $value : null;
    }

    /**
     * Save article-category relation.
     *
     * @param int $articleId
     * @param int|null $categoryId
     * @return void
     */
    public function saveArticleCategory(int $articleId, ?int $categoryId): void
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName(self::TABLE);
        $connection->delete($table, ['article_id = ?' => $articleId]);
        if ($categoryId > 0) {
            $connection->insert($table, [
                'article_id' => $articleId,
                'category_id' => $categoryId,
            ]);
        }
    }

    /**
     * Get article ids by category id.
     *
     * @param int $categoryId
     * @return array
     */
    public function getArticleIdsByCategoryId(int $categoryId): array
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from($this->resource->getTableName(self::TABLE), 'article_id')
            ->where('category_id = ?', $categoryId);
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
