<?php
declare(strict_types=1);

namespace Venbhas\Blog\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Venbhas\Blog\Model\CategoryFactory;
use Venbhas\Blog\Model\ResourceModel\Category as CategoryResource;

class Delete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Venbhas_Blog::category_delete';

    /** @var CategoryFactory */
    private $categoryFactory;

    /** @var CategoryResource */
    private $categoryResource;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param CategoryFactory $categoryFactory
     * @param CategoryResource $categoryResource
     */
    public function __construct(Context $context, CategoryFactory $categoryFactory, CategoryResource $categoryResource)
    {
        parent::__construct($context);
        $this->categoryFactory = $categoryFactory;
        $this->categoryResource = $categoryResource;
    }

    /**
     * Execute action.
     *
     * @return ResultInterface
     */
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
        if ((int) $model->getData('children_count') > 0) {
            $this->messageManager->addErrorMessage(
                __('Cannot delete category with subcategories. Delete or move subcategories first.')
            );
            return $resultRedirect->setPath('*/*/edit', ['category_id' => $id]);
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
