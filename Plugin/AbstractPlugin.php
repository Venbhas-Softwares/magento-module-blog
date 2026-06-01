<?php
declare(strict_types=1);

namespace Venbhas\Blog\Plugin;

use Venbhas\Blog\Model\Config\ModuleEnabledGuard;

/**
 * Base plugin: no-op when the module is disabled in store configuration.
 */
abstract class AbstractPlugin
{
    /** @var ModuleEnabledGuard */
    private $moduleEnabledGuard;

    /**
     * @param ModuleEnabledGuard $moduleEnabledGuard
     */
    public function __construct(ModuleEnabledGuard $moduleEnabledGuard)
    {
        $this->moduleEnabledGuard = $moduleEnabledGuard;
    }

    /**
     * Whether the blog module is enabled for the given store.
     *
     * @param int|null $storeId
     * @return bool
     */
    protected function isModuleEnabled(?int $storeId = null): bool
    {
        return $this->moduleEnabledGuard->isEnabled($storeId);
    }
}
