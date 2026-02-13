<?php
declare(strict_types=1);

namespace Venbhas\Article\Controller\Category;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Venbhas\Article\Model\CategoryFactory;
use Venbhas\Article\Model\ResourceModel\Category as CategoryResource;

class View extends Action implements HttpGetActionInterface
{
    /** @var PageFactory */
    private $resultPageFactory;

    /** @var ForwardFactory */
    private $resultForwardFactory;

    /** @var CategoryFactory */
    private $categoryFactory;

    /** @var CategoryResource */
    private $categoryResource;

    /** @var Registry */
    private $registry;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ForwardFactory $resultForwardFactory,
        CategoryFactory $categoryFactory,
        CategoryResource $categoryResource,
        Registry $registry
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->categoryFactory = $categoryFactory;
        $this->categoryResource = $categoryResource;
        $this->registry = $registry;
    }

    public function execute(): ResultInterface
    {
        
        $id = (int) $this->getRequest()->getParam('id');
        $urlKey = $this->getRequest()->getParam('url_key');
        $category = $this->categoryFactory->create();
        if ($urlKey) {
            $category->load($urlKey, 'url_key');
        } elseif ($id) {
            $this->categoryResource->load($category, $id);
        }
        if (!$category->getId() || !$category->getStatus()) {
            $resultForward = $this->resultForwardFactory->create();
            return $resultForward->forward('noroute');
        }
        $this->registry->register('current_article_category', $category);
        $this->getRequest()->setParam('category_id', $category->getId());
        return $this->resultPageFactory->create();
    }
}
