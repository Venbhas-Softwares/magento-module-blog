<?php
declare(strict_types=1);

namespace Venbhas\Article\Controller\Adminhtml\Comment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Venbhas\Article\Model\CommentFactory;
use Venbhas\Article\Model\ResourceModel\Comment as CommentResource;

class Delete extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Venbhas_Article::comment_delete';

    /** @var CommentFactory */
    private $commentFactory;

    /** @var CommentResource */
    private $commentResource;

    public function __construct(Context $context, CommentFactory $commentFactory, CommentResource $commentResource)
    {
        parent::__construct($context);
        $this->commentFactory = $commentFactory;
        $this->commentResource = $commentResource;
    }

    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = (int) $this->getRequest()->getParam('comment_id');
        if (!$id) {
            $this->messageManager->addErrorMessage(__('We can\'t find a comment to delete.'));
            return $resultRedirect->setPath('*/*/');
        }
        $model = $this->commentFactory->create();
        $this->commentResource->load($model, $id);
        if (!$model->getId()) {
            $this->messageManager->addErrorMessage(__('This comment no longer exists.'));
            return $resultRedirect->setPath('*/*/');
        }
        try {
            $this->commentResource->delete($model);
            $this->messageManager->addSuccessMessage(__('The comment has been deleted.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect->setPath('*/*/edit', ['comment_id' => $id]);
        }
        return $resultRedirect->setPath('*/*/');
    }
}
