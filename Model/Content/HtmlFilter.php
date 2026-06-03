<?php
declare(strict_types=1);

namespace Venbhas\Blog\Model\Content;

use Magento\Cms\Model\Template\FilterProvider;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Renders WYSIWYG / Page Builder HTML and Magento directives on the storefront.
 */
class HtmlFilter
{
    /** @var FilterProvider */
    private $filterProvider;

    /** @var StoreManagerInterface */
    private $storeManager;

    public function __construct(
        FilterProvider $filterProvider,
        StoreManagerInterface $storeManager
    ) {
        $this->filterProvider = $filterProvider;
        $this->storeManager = $storeManager;
    }

    /**
     * Process HTML through the CMS page filter (widgets, media URLs, Page Builder markup).
     */
    public function filter(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $html = $this->normalizeStoredHtml($html);
        $storeId = (int) $this->storeManager->getStore()->getId();

        return (string) $this->filterProvider->getPageFilter()
            ->setStoreId($storeId)
            ->filter($html);
    }

    /**
     * Decode HTML saved from admin WYSIWYG / Page Builder so filters can process markup.
     */
    private function normalizeStoredHtml(string $html): string
    {
        if (str_contains($html, '&lt;') || str_contains($html, '&gt;') || str_contains($html, '&#')) {
            $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return $html;
    }
}
