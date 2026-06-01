<?php
declare(strict_types=1);

namespace Venbhas\Blog\Plugin\Framework\View\Page\Config;

use Magento\Framework\Escaper;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Config\Renderer;
use Venbhas\Blog\Model\SeoMetaApplier;

/**
 * Renders blog SEO meta tags once from values registered by SeoMetaApplier.
 */
class RendererPlugin
{
    private const BLOG_SEO_META = ['title', 'description', 'keywords', 'robots'];

    /** @var Config */
    private $pageConfig;

    /** @var Escaper */
    private $escaper;

    /** @var SeoMetaApplier */
    private $seoMetaApplier;

    /**
     * Initialize SEO renderer plugin dependencies.
     *
     * @param Config $pageConfig
     * @param Escaper $escaper
     * @param SeoMetaApplier $seoMetaApplier
     */
    public function __construct(
        Config $pageConfig,
        Escaper $escaper,
        SeoMetaApplier $seoMetaApplier
    ) {
        $this->pageConfig = $pageConfig;
        $this->escaper = $escaper;
        $this->seoMetaApplier = $seoMetaApplier;
    }

    /**
     * Output all four blog SEO meta tags (including empty values) and skip Magento defaults.
     *
     * @param Renderer $subject
     * @param callable $proceed
     * @return string
     */
    public function aroundRenderMetadata(Renderer $subject, callable $proceed): string
    {
        $seoMeta = $this->seoMetaApplier->getRegisteredMeta();
        if ($seoMeta === null) {
            return $proceed();
        }

        $this->removeDefaultSeoMetadata();

        return $this->renderBlogSeoMetaTags($seoMeta) . $proceed();
    }

    /**
     * Prevent Magento from replacing blog SEO meta with page title / store defaults.
     *
     * @param Renderer $subject
     * @param callable $proceed
     * @param string $name
     * @param mixed $content
     * @return mixed
     */
    public function aroundProcessMetadataContent(
        Renderer $subject,
        callable $proceed,
        string $name,
        $content
    ) {
        if ($this->seoMetaApplier->getRegisteredMeta() !== null
            && in_array($name, self::BLOG_SEO_META, true)
        ) {
            return '';
        }

        return $proceed($name, $content);
    }

    /**
     * Remove default page config SEO metadata before blog tags render.
     *
     * @return void
     */
    private function removeDefaultSeoMetadata(): void
    {
        $reflection = new \ReflectionClass($this->pageConfig);
        $property = $reflection->getProperty('metadata');
        $property->setAccessible(true);

        $metadata = $property->getValue($this->pageConfig);
        foreach (self::BLOG_SEO_META as $name) {
            unset($metadata[$name]);
        }

        $property->setValue($this->pageConfig, $metadata);
    }

    /**
     * Render blog SEO meta tags from registered values.
     *
     * @param array $seoMeta SEO meta values keyed by tag name
     * @return string
     */
    private function renderBlogSeoMetaTags(array $seoMeta): string
    {
        $result = '';
        foreach (self::BLOG_SEO_META as $name) {
            $content = $seoMeta[$name] ?? '';
            $result .= sprintf(
                '<meta name="%s" content="%s"/>' . "\n",
                $this->escaper->escapeHtmlAttr($name),
                $this->escaper->escapeHtmlAttr($content)
            );
        }

        return $result;
    }
}
