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
    /**
     * @var FilterProvider
     */
    private FilterProvider $filterProvider;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @param FilterProvider $filterProvider CMS page filter provider
     * @param StoreManagerInterface $storeManager Store manager
     */
    public function __construct(
        FilterProvider $filterProvider,
        StoreManagerInterface $storeManager
    ) {
        $this->filterProvider = $filterProvider;
        $this->storeManager = $storeManager;
    }

    /**
     * Process HTML through the CMS page filter (widgets, media URLs, Page Builder markup).
     *
     * @param string|null $html Raw HTML from the database
     * @return string
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
     *
     * @param string $html Entity-encoded HTML
     * @return string
     */
    private function normalizeStoredHtml(string $html): string
    {
        if (str_contains($html, '&lt;') || str_contains($html, '&gt;') || str_contains($html, '&#')) {
            $html = $this->decodeStoredHtmlEntities($html);
        }

        return $html;
    }

    /**
     * Decode common HTML entities produced by admin WYSIWYG storage.
     *
     * @param string $html Entity-encoded HTML
     * @return string
     */
    private function decodeStoredHtmlEntities(string $html): string
    {
        $html = str_replace(
            ['&lt;', '&gt;', '&quot;', '&apos;', '&#039;'],
            ['<', '>', '"', "'", "'"],
            $html
        );

        $html = (string) preg_replace_callback(
            '/&#(\d+);/',
            static function (array $matches): string {
                $code = (int) $matches[1];
                return $code > 0 ? mb_chr($code, 'UTF-8') : $matches[0];
            },
            $html
        );

        return (string) preg_replace_callback(
            '/&#x([0-9a-f]+);/i',
            static function (array $matches): string {
                $code = (int) hexdec($matches[1]);
                return $code > 0 ? mb_chr($code, 'UTF-8') : $matches[0];
            },
            $html
        );
    }
}
