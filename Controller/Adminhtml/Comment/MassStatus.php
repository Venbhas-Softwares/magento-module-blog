<?php
declare(strict_types=1);

namespace Venbhas\Blog\Controller\Adminhtml\Comment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Ui\Component\MassAction\Filter;
use Venbhas\Blog\Model\Comment;
use Venbhas\Blog\Model\ResourceModel\Comment as CommentResource;
use Venbhas\Blog\Model\ResourceModel\Comment\CollectionFactory;

class MassStatus extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Venbhas_Blog::comment_save';

    /** @var Filter */
    private $filter;

    /** @var CollectionFactory */
    private $collectionFactory;

    /** @var CommentResource */
    private $commentResource;

    /**
     * Initialize mass status action dependencies.
     *
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param CommentResource $commentResource
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        CommentResource $commentResource
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->commentResource = $commentResource;
    }

    /**
     * Mass-update comment status from admin grid.
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        $status = (int) $this->getRequest()->getParam('status');
        $allowed = [Comment::STATUS_PENDING, Comment::STATUS_APPROVED, Comment::STATUS_REJECTED];
        if (!in_array($status, $allowed, true)) {
            $this->messageManager->addErrorMessage(__('Invalid status value.'));
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $updated = 0;

            foreach ($collection as $comment) {
                $comment->setData('status', $status);
                $this->commentResource->save($comment);
                $updated++;
            }

            $this->messageManager->addSuccessMessage(
                __('A total of %1 comment(s) have been updated.', $updated)
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect->setPath('*/*/');
    }
}
