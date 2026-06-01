<?php
declare(strict_types=1);

namespace Venbhas\Blog\Controller;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\ResultInterface;
use Venbhas\Blog\Model\Config\ModuleEnabledGuard;

/**
 * Frontend action that returns 404 when the module is disabled in store config.
 */
abstract class AbstractEnabledAction extends Action
{
    /** @var ModuleEnabledGuard */
    private $moduleEnabledGuard;

    /** @var ForwardFactory */
    protected $resultForwardFactory;

    /**
     * Initialize enabled-action dependencies.
     *
     * @param Context $context
     * @param ModuleEnabledGuard $moduleEnabledGuard
     * @param ForwardFactory $resultForwardFactory
     */
    public function __construct(
        Context $context,
        ModuleEnabledGuard $moduleEnabledGuard,
        ForwardFactory $resultForwardFactory
    ) {
        parent::__construct($context);
        $this->moduleEnabledGuard = $moduleEnabledGuard;
        $this->resultForwardFactory = $resultForwardFactory;
    }

    /**
     * Forward to noroute when the module is disabled.
     *
     * @param int|null $storeId
     * @return ResultInterface|null Noroute result when disabled, null when enabled
     */
    protected function norouteIfModuleDisabled(?int $storeId = null): ?ResultInterface
    {
        if ($this->moduleEnabledGuard->isEnabled($storeId)) {
            return null;
        }

        return $this->resultForwardFactory->create()->forward('noroute');
    }

    /**
     * Whether the blog module is enabled for the store.
     *
     * @param int|null $storeId
     * @return bool
     */
    protected function isModuleEnabled(?int $storeId = null): bool
    {
        return $this->moduleEnabledGuard->isEnabled($storeId);
    }

    /**
     * Create a noroute forward result.
     *
     * @return ResultInterface
     */
    protected function forwardNoroute(): ResultInterface
    {
        return $this->resultForwardFactory->create()->forward('noroute');
    }
}
