<?php
declare(strict_types=1);

namespace Venbhas\Article\Controller\Adminhtml\Article;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Venbhas\Article\Model\ArticleFactory;

class Edit extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Venbhas_Article::article_save';

    /** @var PageFactory */
    private $resultPageFactory;

    /** @var Registry */
    private $coreRegistry;

    /** @var ArticleFactory */
    private $articleFactory;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Registry $coreRegistry
     * @param ArticleFactory $articleFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Registry $coreRegistry,
        ArticleFactory $articleFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->coreRegistry = $coreRegistry;
        $this->articleFactory = $articleFactory;
    }

    /**
     * Execute action.
     *
     * @return \Magento\Framework\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $id = (int) $this->getRequest()->getParam('article_id');
        $model = $this->articleFactory->create();
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This article no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }
        $this->coreRegistry->register('venbhas_article', $model);
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Venbhas_Article::article_manage');
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getId() ? __('Edit Article "%1"', $model->getTitle()) : __('New Article')
        );
        return $resultPage;
    }
}
