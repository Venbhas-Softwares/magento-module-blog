<?php
declare(strict_types=1);

namespace Venbhas\Blog\Controller\Category;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Venbhas\Blog\Controller\AbstractEnabledAction;
use Venbhas\Blog\Model\CategoryFactory;
use Venbhas\Blog\Model\Config\ModuleEnabledGuard;
use Venbhas\Blog\Model\ResourceModel\Category as CategoryResource;
use Venbhas\Blog\Model\SeoMetaApplier;

class View extends AbstractEnabledAction implements HttpGetActionInterface
{
    /** @var PageFactory */
    private $resultPageFactory;

    /** @var CategoryFactory */
    private $categoryFactory;

    /** @var CategoryResource */
    private $categoryResource;

    /** @var Registry */
    private $registry;

    /** @var SeoMetaApplier */
    private $seoMetaApplier;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param ModuleEnabledGuard $moduleEnabledGuard
     * @param ForwardFactory $resultForwardFactory
     * @param PageFactory $resultPageFactory
     * @param CategoryFactory $categoryFactory
     * @param CategoryResource $categoryResource
     * @param Registry $registry
     * @param SeoMetaApplier $seoMetaApplier
     */
    public function __construct(
        Context $context,
        ModuleEnabledGuard $moduleEnabledGuard,
        ForwardFactory $resultForwardFactory,
        PageFactory $resultPageFactory,
        CategoryFactory $categoryFactory,
        CategoryResource $categoryResource,
        Registry $registry,
        SeoMetaApplier $seoMetaApplier
    ) {
        parent::__construct($context, $moduleEnabledGuard, $resultForwardFactory);
        $this->resultPageFactory = $resultPageFactory;
        $this->categoryFactory = $categoryFactory;
        $this->categoryResource = $categoryResource;
        $this->registry = $registry;
        $this->seoMetaApplier = $seoMetaApplier;
    }

    /**
     * Execute action.
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        if ($denied = $this->norouteIfModuleDisabled()) {
            return $denied;
        }

        $id = (int) $this->getRequest()->getParam('id');
        $urlKey = $this->getRequest()->getParam('url_key');
        $category = $this->categoryFactory->create();
        if ($urlKey) {
            $category->load($urlKey, 'url_key');
        } elseif ($id) {
            $this->categoryResource->load($category, $id);
        }
        if (!$category->getId() || !$category->getStatus()) {
            return $this->forwardNoroute();
        }
        $this->registry->register('current_article_category', $category);
        $this->getRequest()->setParam('category_id', $category->getId());

        $resultPage = $this->resultPageFactory->create();
        $this->seoMetaApplier->apply(
            $resultPage,
            $category,
            (string) $category->getName(),
            SeoMetaApplier::ROBOTS_CONFIG_CATEGORY
        );

        return $resultPage;
    }
}
