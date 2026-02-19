<?php
declare(strict_types=1);

namespace Venbhas\Article\Controller\Adminhtml\Article;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Venbhas\Article\Model\ArticleFactory;
use Venbhas\Article\Model\ResourceModel\Article as ArticleResource;

class Delete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Venbhas_Article::article_delete';

    /** @var ArticleFactory */
    private $articleFactory;

    /** @var ArticleResource */
    private $articleResource;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param ArticleFactory $articleFactory
     * @param ArticleResource $articleResource
     */
    public function __construct(Context $context, ArticleFactory $articleFactory, ArticleResource $articleResource)
    {
        parent::__construct($context);
        $this->articleFactory = $articleFactory;
        $this->articleResource = $articleResource;
    }

    /**
     * Execute action.
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = (int) $this->getRequest()->getParam('article_id');
        if (!$id) {
            $this->messageManager->addErrorMessage(__('We can\'t find an article to delete.'));
            return $resultRedirect->setPath('*/*/');
        }
        $model = $this->articleFactory->create();
        $this->articleResource->load($model, $id);
        if (!$model->getId()) {
            $this->messageManager->addErrorMessage(__('This article no longer exists.'));
            return $resultRedirect->setPath('*/*/');
        }
        try {
            $this->articleResource->delete($model);
            $this->messageManager->addSuccessMessage(__('The article has been deleted.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect->setPath('*/*/edit', ['article_id' => $id]);
        }
        return $resultRedirect->setPath('*/*/');
    }
}
