<?php
declare(strict_types=1);

namespace Venbhas\Blog\Model\ResourceModel;

/**
 * Related articles persistence reader for blog entities.
 */
interface RelatedArticlesResourceInterface
{
    /**
     * Get related article ids for entity.
     *
     * @param int $entityId
     * @return int[]
     */
    public function getRelatedArticleIds(int $entityId): array;
}
