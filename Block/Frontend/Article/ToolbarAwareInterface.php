<?php
declare(strict_types=1);

namespace Venbhas\Blog\Block\Frontend\Article;

/**
 * Article list blocks that expose a catalog-style toolbar.
 */
interface ToolbarAwareInterface
{
    /**
     * @return string
     */
    public function getToolbarHtml(): string;

    /**
     * @return string
     */
    public function getCurrentSortOrder(): string;
}
