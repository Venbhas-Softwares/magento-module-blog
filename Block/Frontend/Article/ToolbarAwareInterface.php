<?php
declare(strict_types=1);

namespace Venbhas\Blog\Block\Frontend\Article;

/**
 * Article list blocks that expose a catalog-style toolbar.
 */
interface ToolbarAwareInterface
{
    /**
     * Render the top list toolbar HTML.
     *
     * @return string
     */
    public function getToolbarHtml(): string;

    /**
     * Get the active list sort order code.
     *
     * @return string
     */
    public function getCurrentSortOrder(): string;
}
