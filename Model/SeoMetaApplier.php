<?php
declare(strict_types=1);

namespace Venbhas\Blog\Model;

use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Framework\View\Result\Page;

/**
 * Registers blog SEO meta for head rendering on article/category view pages.
 */
class SeoMetaApplier
{
    public const REGISTRY_KEY = 'venbhas_blog_seo_meta';

    /** @var Registry */
    private $registry;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Store SEO meta values and set the browser tab title.
     *
     * @param Page $resultPage
     * @param DataObject $entity
     * @param string $browserTitle
     * @return void
     */
    public function apply(Page $resultPage, DataObject $entity, string $browserTitle): void
    {
        $meta = [
            'title' => trim((string) $entity->getData('meta_title')),
            'description' => trim((string) $entity->getData('meta_description')),
            'keywords' => trim((string) $entity->getData('meta_keywords')),
            'robots' => trim((string) $entity->getData('meta_robots')),
        ];

        if ($this->registry->registry(self::REGISTRY_KEY)) {
            $this->registry->unregister(self::REGISTRY_KEY);
        }
        $this->registry->register(self::REGISTRY_KEY, $meta);

        $resultPage->getConfig()->getTitle()->set($browserTitle);
    }

    /**
     * Return SEO meta registered for the current request.
     *
     * @return array<string, string>|null
     */
    public function getRegisteredMeta(): ?array
    {
        $meta = $this->registry->registry(self::REGISTRY_KEY);

        return is_array($meta) ? $meta : null;
    }
}
