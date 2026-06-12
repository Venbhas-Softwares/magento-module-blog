<?php
declare(strict_types=1);

namespace Venbhas\Blog\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Venbhas\Blog\Model\CategoryFactory;

class Edit extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Venbhas_Blog::category_save';

    /** @var PageFactory */
    private $resultPageFactory;

    /** @var Registry */
    private $coreRegistry;

    /** @var CategoryFactory */
    private $categoryFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Registry $coreRegistry
     * @param CategoryFactory $categoryFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Registry $coreRegistry,
        CategoryFactory $categoryFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->coreRegistry = $coreRegistry;
        $this->categoryFactory = $categoryFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $id = (int) $this->getRequest()->getParam('category_id');
        $model = $this->categoryFactory->create();

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This category no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        } elseif ($this->getRequest()->has('parent')) {
            $model->setData('parent_id', (int) $this->getRequest()->getParam('parent'));
        } else {
            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }

        $this->coreRegistry->register('venbhas_blog_category', $model);
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Venbhas_Blog::category_manage');
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getId() ? __('Edit Category "%1"', $model->getName()) : __('New Category')
        );

        return $resultPage;
    }
}
