<?php
declare(strict_types=1);

namespace Venbhas\Blog\Controller\Search;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\View\Result\PageFactory;
use Venbhas\Blog\Controller\AbstractEnabledAction;
use Venbhas\Blog\Model\Config\ModuleEnabledGuard;

class Index extends AbstractEnabledAction implements HttpGetActionInterface
{
    /** @var PageFactory */
    private $resultPageFactory;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param ModuleEnabledGuard $moduleEnabledGuard
     * @param ForwardFactory $resultForwardFactory
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        ModuleEnabledGuard $moduleEnabledGuard,
        ForwardFactory $resultForwardFactory,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context, $moduleEnabledGuard, $resultForwardFactory);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Execute action.
     *
     * @return \Magento\Framework\View\Result\Page|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if ($denied = $this->norouteIfModuleDisabled()) {
            return $denied;
        }

        return $this->resultPageFactory->create();
    }
}
