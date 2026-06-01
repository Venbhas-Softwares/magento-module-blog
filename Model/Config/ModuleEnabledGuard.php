<?php
declare(strict_types=1);

namespace Venbhas\Blog\Model\Config;

use Venbhas\Blog\Model\Config;

/**
 * Central guard for store-config module enable flag (venbhas_blog/general/enabled).
 */
class ModuleEnabledGuard
{
    /** @var Config */
    private $config;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Whether blog frontend features should run for the given store.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return $this->config->isModuleEnabled($storeId);
    }
}
