<?php
declare(strict_types=1);

namespace Venbhas\Article\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Venbhas\Article\Model\CategoryFactory;
use Venbhas\Article\Model\ResourceModel\Category as CategoryResource;

class Delete extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Venbhas_Article::category_delete';

    /** @var CategoryFactory */
    private $categoryFactory;

    /** @var CategoryResource */
    private $categoryResource;

    public function __construct(Context $context, CategoryFactory $categoryFactory, CategoryResource $categoryResource)
    {
        parent::__construct($context);
        $this->categoryFactory = $categoryFactory;
        $this->categoryResource = $categoryResource;
    }

    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = (int) $this->getRequest()->getParam('category_id');
        if (!$id) {
            $this->messageManager->addErrorMessage(__('We can\'t find a category to delete.'));
            return $resultRedirect->setPath('*/*/');
        }
        $model = $this->categoryFactory->create();
        $this->categoryResource->load($model, $id);
        if (!$model->getId()) {
            $this->messageManager->addErrorMessage(__('This category no longer exists.'));
            return $resultRedirect->setPath('*/*/');
        }
        try {
            $this->categoryResource->delete($model);
            $this->messageManager->addSuccessMessage(__('The category has been deleted.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect->setPath('*/*/edit', ['category_id' => $id]);
        }
        return $resultRedirect->setPath('*/*/');
    }
}
