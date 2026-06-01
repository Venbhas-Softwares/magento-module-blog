<?php
declare(strict_types=1);

namespace Venbhas\Blog\Model\ResourceModel;

/**
 * Related products persistence reader for blog entities.
 */
interface RelatedProductsResourceInterface
{
    /**
     * Get related product ids for entity.
     *
     * @param int $entityId
     * @return array
     */
    public function getRelatedProductIds(int $entityId): array;
}
