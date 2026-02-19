<?php
declare(strict_types=1);

namespace Venbhas\Article\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Venbhas\Article\Model\CategoryFactory;

class Edit extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Venbhas_Article::category_save';

    /** @var PageFactory */
    private $resultPageFactory;

    /** @var Registry */
    private $coreRegistry;

    /** @var CategoryFactory */
    private $categoryFactory;

    /**
     * Constructor.
     *
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
     * Execute action.
     *
     * @return \Magento\Framework\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect
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
        }
        $this->coreRegistry->register('venbhas_article_category', $model);
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Venbhas_Article::category_manage');
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getId() ? __('Edit Category "%1"', $model->getName()) : __('New Category')
        );
        return $resultPage;
    }
}
