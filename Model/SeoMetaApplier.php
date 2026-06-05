<?php
declare(strict_types=1);

namespace Venbhas\Blog\Model;

use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Framework\View\Result\Page;
use Venbhas\Blog\Model\Config\Source\MetaRobots;

/**
 * Registers blog SEO meta for head rendering on article/category view pages.
 */
class SeoMetaApplier
{
    public const REGISTRY_KEY = 'venbhas_blog_seo_meta';
    public const ROBOTS_CONFIG_POST = 'post';
    public const ROBOTS_CONFIG_CATEGORY = 'category';

    /**
     * @var Registry
     */
    private Registry $registry;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @param Registry $registry Application registry
     * @param Config $config Blog configuration
     */
    public function __construct(Registry $registry, Config $config)
    {
        $this->registry = $registry;
        $this->config = $config;
    }

    /**
     * Store SEO meta values and set the browser tab title.
     *
     * @param Page $resultPage
     * @param DataObject $entity
     * @param string $fallbackTitle Article title or category name when meta_title is empty
     * @param string $robotsConfigKey Config key: {@see self::ROBOTS_CONFIG_POST} or {@see self::ROBOTS_CONFIG_CATEGORY}
     * @return void
     */
    public function apply(
        Page $resultPage,
        DataObject $entity,
        string $fallbackTitle,
        string $robotsConfigKey = self::ROBOTS_CONFIG_POST
    ): void {
        $metaTitle = $this->resolveMetaTitle($entity, $fallbackTitle);

        $meta = [
            'title' => $metaTitle,
            'description' => trim((string) $entity->getData('meta_description')),
            'keywords' => trim((string) $entity->getData('meta_keywords')),
            'robots' => $this->resolveRobotsDirective($entity, $robotsConfigKey),
        ];

        if ($this->registry->registry(self::REGISTRY_KEY)) {
            $this->registry->unregister(self::REGISTRY_KEY);
        }
        $this->registry->register(self::REGISTRY_KEY, $meta);

        $resultPage->getConfig()->getTitle()->set($metaTitle);
    }

    /**
     * Use meta_title when set; otherwise fall back to the entity display name.
     *
     * @param DataObject $entity
     * @param string $fallbackTitle
     * @return string
     */
    private function resolveMetaTitle(DataObject $entity, string $fallbackTitle): string
    {
        $metaTitle = trim((string) $entity->getData('meta_title'));
        if ($metaTitle !== '') {
            return $metaTitle;
        }

        return trim($fallbackTitle);
    }

    /**
     * Resolve robots directive from entity override or store configuration.
     *
     * @param DataObject $entity
     * @param string $robotsConfigKey
     * @return string
     */
    private function resolveRobotsDirective(DataObject $entity, string $robotsConfigKey): string
    {
        $metaRobots = MetaRobots::normalizeValue($entity->getData('meta_robots'));
        if ($metaRobots !== null) {
            return MetaRobots::toDirective($metaRobots);
        }

        $configValue = $robotsConfigKey === self::ROBOTS_CONFIG_CATEGORY
            ? $this->config->getCategoryMetaRobots()
            : $this->config->getPostMetaRobots();

        return MetaRobots::toDirective($configValue);
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
