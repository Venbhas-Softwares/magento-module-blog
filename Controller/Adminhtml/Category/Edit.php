<?php
declare(strict_types=1);

namespace Venbhas\Blog\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Venbhas\Blog\Model\Category;
use Venbhas\Blog\Model\CategoryFactory;
use Venbhas\Blog\Model\ResourceModel\Category\CollectionFactory;

class Edit extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Venbhas_Blog::category_save';

    /** @var PageFactory */
    private $resultPageFactory;

    /** @var Registry */
    private $coreRegistry;

    /** @var CategoryFactory */
    private $categoryFactory;

    /** @var CollectionFactory */
    private $collectionFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Registry $coreRegistry
     * @param CategoryFactory $categoryFactory
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Registry $coreRegistry,
        CategoryFactory $categoryFactory,
        CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->coreRegistry = $coreRegistry;
        $this->categoryFactory = $categoryFactory;
        $this->collectionFactory = $collectionFactory;
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
            $collection = $this->collectionFactory->create();
            $collection->setOrder('level', 'ASC')->setOrder('position', 'ASC')->setOrder('category_id', 'ASC');
            $collection->setPageSize(1);
            $firstId = (int) $collection->getFirstItem()->getId();
            if ($firstId) {
                return $this->resultRedirectFactory->create()->setPath(
                    '*/*/edit',
                    ['category_id' => $firstId]
                );
            }
            $model->setData('parent_id', Category::TREE_ROOT_ID);
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
