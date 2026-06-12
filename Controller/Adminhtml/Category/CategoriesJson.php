<?php
declare(strict_types=1);

namespace Venbhas\Blog\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\LayoutFactory;
use Venbhas\Blog\Block\Adminhtml\Category\Tree;
use Venbhas\Blog\Model\ResourceModel\Category\Tree as CategoryTreeResource;

class CategoriesJson extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Venbhas_Blog::category';

    /** @var JsonFactory */
    private $resultJsonFactory;

    /** @var LayoutFactory */
    private $layoutFactory;

    /** @var Session */
    private $authSession;

    /** @var CategoryTreeResource */
    private $categoryTreeResource;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param LayoutFactory $layoutFactory
     * @param Session $authSession
     * @param CategoryTreeResource $categoryTreeResource
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        LayoutFactory $layoutFactory,
        Session $authSession,
        CategoryTreeResource $categoryTreeResource
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->layoutFactory = $layoutFactory;
        $this->authSession = $authSession;
        $this->categoryTreeResource = $categoryTreeResource;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if ($this->getRequest()->getParam('expand_all')) {
            $this->authSession->setIsTreeWasExpanded(true);
        } else {
            $this->authSession->setIsTreeWasExpanded(false);
        }

        $categoryId = (int) $this->getRequest()->getPost('id');
        $resultJson = $this->resultJsonFactory->create();

        if ($categoryId <= 0) {
            return $resultJson->setData(['error' => __('Category ID is required')]);
        }

        $parentNode = $this->categoryTreeResource->loadNode($categoryId);
        /** @var Tree $block */
        $block = $this->layoutFactory->create()->createBlock(Tree::class);

        return $resultJson->setJsonData($block->getTreeJson($parentNode));
    }
}
