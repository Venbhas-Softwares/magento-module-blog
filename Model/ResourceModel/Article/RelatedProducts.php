<?php
declare(strict_types=1);

namespace Venbhas\Article\Model\ResourceModel\Article;

use Magento\Framework\App\ResourceConnection;

class RelatedProducts
{
    private const TABLE = 'venbhas_article_related_products';

    /** @var ResourceConnection */
    private $resource;

    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    public function saveRelatedProducts(int $articleId, array $productIds): void
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName(self::TABLE);
        $connection->delete($table, ['article_id = ?' => $articleId]);
        foreach ($productIds as $productId) {
            if (empty($productId)) {
                continue;
            }
            $connection->insert($table, [
                'article_id' => $articleId,
                'product_id' => (int) $productId,
            ]);
        }
    }

    public function getRelatedProductIds(int $articleId): array
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from($this->resource->getTableName(self::TABLE), 'product_id')
            ->where('article_id = ?', $articleId);
        return $connection->fetchCol($select);
    }

    public function getProductIdsBySkus(array $skus): array
    {
        if (empty($skus)) {
            return [];
        }
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from($this->resource->getTableName('catalog_product_entity'), 'entity_id')
            ->where('sku IN (?)', $skus);
        return $connection->fetchCol($select) ?: [];
    }

    public function getSkusByProductIds(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from($this->resource->getTableName('catalog_product_entity'), 'sku')
            ->where('entity_id IN (?)', $productIds);
        return $connection->fetchCol($select) ?: [];
    }
}
