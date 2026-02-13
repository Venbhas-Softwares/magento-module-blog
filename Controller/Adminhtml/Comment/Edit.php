<?php
declare(strict_types=1);

namespace Venbhas\Article\Controller\Adminhtml\Comment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Venbhas\Article\Model\CommentFactory;

class Edit extends Action implements HttpGetActionInterface
{
    const ADMIN_RESOURCE = 'Venbhas_Article::comment_save';

    /** @var PageFactory */
    private $resultPageFactory;

    /** @var Registry */
    private $coreRegistry;

    /** @var CommentFactory */
    private $commentFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Registry $coreRegistry,
        CommentFactory $commentFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->coreRegistry = $coreRegistry;
        $this->commentFactory = $commentFactory;
    }

    public function execute()
    {
        $id = (int) $this->getRequest()->getParam('comment_id');
        $model = $this->commentFactory->create();
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This comment no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }
        $this->coreRegistry->register('venbhas_article_comment', $model);
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Venbhas_Article::comment_manage');
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getId() ? __('Edit Comment') : __('New Comment')
        );
        return $resultPage;
    }
}
