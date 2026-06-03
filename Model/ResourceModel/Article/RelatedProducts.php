<?php
declare(strict_types=1);

namespace Venbhas\Blog\Model\ResourceModel\Article;

use Magento\Framework\App\ResourceConnection;
use Venbhas\Blog\Model\ResourceModel\RelatedProductsResourceInterface;

/**
 * Article related products resource.
 */
class RelatedProducts implements RelatedProductsResourceInterface
{
    private const TABLE = 'venbhas_article_related_products';

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
     * Save related products for article.
     *
     * @param int $articleId
     * @param array $productIds
     * @return void
     */
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

    /**
     * Get related product ids for article.
     *
     * @param int $articleId
     * @return array
     */
    public function getRelatedProductIds(int $articleId): array
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from($this->resource->getTableName(self::TABLE), 'product_id')
            ->where('article_id = ?', $articleId);
        $result = $connection->fetchCol($select);
        return is_array($result) ? $result : [];
    }
}
