<?php

declare(strict_types=1);

namespace Venbhas\Blog\Plugin\Backend\App;

use Magento\Backend\App\AbstractAction;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Venbhas\Blog\Model\Config\ModuleEnabledGuard;

/**
 * Blocks admin blog/* routes when the module is disabled at default scope.
 */
class DispatchGuardPlugin
{
    private const BLOG_MODULE = 'blog';

    /**
     * @var ModuleEnabledGuard
     */
    private ModuleEnabledGuard $moduleEnabledGuard;

    /**
     * @var MessageManagerInterface
     */
    private MessageManagerInterface $messageManager;

    /**
     * @var RedirectFactory
     */
    private RedirectFactory $resultRedirectFactory;

    /**
     * @param ModuleEnabledGuard $moduleEnabledGuard Module enabled guard
     * @param MessageManagerInterface $messageManager Admin flash messages
     * @param RedirectFactory $resultRedirectFactory Admin redirect result factory
     */
    public function __construct(
        ModuleEnabledGuard $moduleEnabledGuard,
        MessageManagerInterface $messageManager,
        RedirectFactory $resultRedirectFactory
    ) {
        $this->moduleEnabledGuard = $moduleEnabledGuard;
        $this->messageManager = $messageManager;
        $this->resultRedirectFactory = $resultRedirectFactory;
    }

    /**
     * Redirect to dashboard when blog admin actions are requested while disabled.
     *
     * @param AbstractAction $subject Backend action
     * @param callable $proceed Original dispatch
     * @param RequestInterface $request HTTP request
     *
     * @return ResponseInterface|ResultInterface
     */
    public function aroundDispatch(AbstractAction $subject, callable $proceed, RequestInterface $request)
    {
        if ($request->getModuleName() === self::BLOG_MODULE
            && !$this->moduleEnabledGuard->isEnabledForAdmin()
        ) {
            $this->messageManager->addErrorMessage(
                __('The Blog module is disabled. Enable it under Stores → Configuration → Venbhas → Blog.')
            );

            /** @var Redirect $redirect */
            $redirect = $this->resultRedirectFactory->create();
            $redirect->setPath('adminhtml/dashboard/index');

            return $redirect;
        }

        return $proceed($request);
    }
}
