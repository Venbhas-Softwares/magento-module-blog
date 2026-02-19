<?php
declare(strict_types=1);

namespace Venbhas\Article\Controller\Article;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Venbhas\Article\Model\ArticleFactory;
use Venbhas\Article\Model\ResourceModel\Article as ArticleResource;

class View extends Action implements HttpGetActionInterface
{
    /** @var PageFactory */
    private $resultPageFactory;

    /** @var ForwardFactory */
    private $resultForwardFactory;

    /** @var ArticleFactory */
    private $articleFactory;

    /** @var ArticleResource */
    private $articleResource;

    /** @var Registry */
    private $registry;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param ForwardFactory $resultForwardFactory
     * @param ArticleFactory $articleFactory
     * @param ArticleResource $articleResource
     * @param Registry $registry
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ForwardFactory $resultForwardFactory,
        ArticleFactory $articleFactory,
        ArticleResource $articleResource,
        Registry $registry
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->articleFactory = $articleFactory;
        $this->articleResource = $articleResource;
        $this->registry = $registry;
    }

    /**
     * Execute action.
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $id = (int) $this->getRequest()->getParam('article_id');
        $urlKey = $this->getRequest()->getParam('url_key');
        $article = $this->articleFactory->create();
        if ($urlKey) {
            $article->load($urlKey, 'url_key');
        } elseif ($id) {
            $this->articleResource->load($article, $id);
        }
        if (!$article->getId() || !$article->getIsActive()) {
            $resultForward = $this->resultForwardFactory->create();
            return $resultForward->forward('noroute');
        }
        $this->registry->register('current_article', $article);
        $this->getRequest()->setParam('article_id', $article->getId());
        return $this->resultPageFactory->create();
    }
}
