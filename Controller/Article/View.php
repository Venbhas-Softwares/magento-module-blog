<?php
declare(strict_types=1);

namespace Venbhas\Blog\Controller\Article;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Venbhas\Blog\Controller\AbstractEnabledAction;
use Venbhas\Blog\Model\ArticleFactory;
use Venbhas\Blog\Model\Config\ModuleEnabledGuard;
use Venbhas\Blog\Model\ResourceModel\Article as ArticleResource;
use Venbhas\Blog\Model\SeoMetaApplier;

class View extends AbstractEnabledAction implements HttpGetActionInterface
{
    /** @var PageFactory */
    private $resultPageFactory;

    /** @var ArticleFactory */
    private $articleFactory;

    /** @var ArticleResource */
    private $articleResource;

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
     * @param ArticleFactory $articleFactory
     * @param ArticleResource $articleResource
     * @param Registry $registry
     * @param SeoMetaApplier $seoMetaApplier
     */
    public function __construct(
        Context $context,
        ModuleEnabledGuard $moduleEnabledGuard,
        ForwardFactory $resultForwardFactory,
        PageFactory $resultPageFactory,
        ArticleFactory $articleFactory,
        ArticleResource $articleResource,
        Registry $registry,
        SeoMetaApplier $seoMetaApplier
    ) {
        parent::__construct($context, $moduleEnabledGuard, $resultForwardFactory);
        $this->resultPageFactory = $resultPageFactory;
        $this->articleFactory = $articleFactory;
        $this->articleResource = $articleResource;
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

        $id = (int) $this->getRequest()->getParam('article_id');
        $urlKey = $this->getRequest()->getParam('url_key');
        $article = $this->articleFactory->create();
        if ($urlKey) {
            $article->load($urlKey, 'url_key');
        } elseif ($id) {
            $this->articleResource->load($article, $id);
        }
        if (!$article->getId() || (int) $article->getData('status') !== 1) {
            return $this->forwardNoroute();
        }
        $this->registry->register('current_article', $article);
        $this->getRequest()->setParam('article_id', $article->getId());

        $resultPage = $this->resultPageFactory->create();
        $this->seoMetaApplier->apply($resultPage, $article, (string) $article->getTitle());

        return $resultPage;
    }
}
