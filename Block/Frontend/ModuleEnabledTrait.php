<?php
declare(strict_types=1);

namespace Venbhas\Blog\Block\Frontend;

use Venbhas\Blog\Model\Config;

/**
 * Suppress block output when the module is disabled in store configuration.
 */
trait ModuleEnabledTrait
{
    /**
     * @inheritdoc
     */
    protected function _toHtml()
    {
        $config = $this->resolveBlogConfig();
        if ($config !== null) {
            $storeId = null;
            try {
                $storeId = (int) $this->_storeManager->getStore()->getId();
            } catch (\Throwable $e) {
                $storeId = null;
            }
            if (!$config->isModuleEnabled($storeId)) {
                return '';
            }
        }

        return parent::_toHtml();
    }

    /**
     * Resolve Config from common property names used in frontend blocks.
     *
     * @return Config|null
     */
    private function resolveBlogConfig(): ?Config
    {
        foreach (['config', 'blogConfig'] as $property) {
            if (property_exists($this, $property)) {
                $value = $this->{$property};
                if ($value instanceof Config) {
                    return $value;
                }
            }
        }

        return null;
    }
}
