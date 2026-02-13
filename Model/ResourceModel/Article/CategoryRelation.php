<?php
declare(strict_types=1);

namespace Venbhas\Article\Model\ResourceModel\Article;

use Magento\Framework\App\ResourceConnection;

class CategoryRelation
{
    private const TABLE = 'venbhas_article_category_relation';

    /** @var ResourceConnection */
    private $resource;

    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

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

    public function getArticleIdsByCategoryId(int $categoryId): array
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from($this->resource->getTableName(self::TABLE), 'article_id')
            ->where('category_id = ?', $categoryId);
        return array_map('intval', $connection->fetchCol($select) ?: []);
    }

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
