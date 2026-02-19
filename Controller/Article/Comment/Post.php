<?php
declare(strict_types=1);

namespace Venbhas\Article\Controller\Article\Comment;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Venbhas\Article\Model\CommentFactory;
use Venbhas\Article\Model\Config;
use Venbhas\Article\Model\ResourceModel\Article as ArticleResource;

class Post extends Action implements HttpPostActionInterface
{
    /** @var RedirectFactory */
    protected $resultRedirectFactory;

    /** @var ManagerInterface */
    protected $messageManager;

    /** @var CommentFactory */
    protected $commentFactory;

    /** @var Config */
    protected $config;

    /** @var StoreManagerInterface */
    protected $storeManager;

    /** @var ArticleResource */
    protected $articleResource;

    /** @var \Venbhas\Article\Model\ArticleFactory */
    protected $articleFactory;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param RedirectFactory $resultRedirectFactory
     * @param ManagerInterface $messageManager
     * @param CommentFactory $commentFactory
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     * @param ArticleResource $articleResource
     * @param \Venbhas\Article\Model\ArticleFactory $articleFactory
     */
    public function __construct(
        Context $context,
        RedirectFactory $resultRedirectFactory,
        ManagerInterface $messageManager,
        CommentFactory $commentFactory,
        Config $config,
        StoreManagerInterface $storeManager,
        ArticleResource $articleResource,
        \Venbhas\Article\Model\ArticleFactory $articleFactory
    ) {
        parent::__construct($context);
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->messageManager = $messageManager;
        $this->commentFactory = $commentFactory;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->articleResource = $articleResource;
        $this->articleFactory = $articleFactory;
    }

    /**
     * Execute action.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $request = $this->getRequest();
        if (!$request->isPost()) {
            return $this->resultRedirectFactory->create()->setPath('/');
        }

        $storeId = (int) $this->storeManager->getStore()->getId();
        if (!$this->config->isCommentsEnabled($storeId)) {
            $this->messageManager->addErrorMessage(__('Comments are disabled.'));
            return $this->getRedirectToArticle((int) $request->getPost('article_id'));
        }

        $articleId = (int) $request->getPost('article_id');
        $userName = trim((string) $request->getPost('user_name', ''));
        $userEmail = trim((string) $request->getPost('user_email', ''));
        $commentText = trim((string) $request->getPost('comment', ''));

        if ($articleId <= 0 || $userName === '' || $userEmail === '' || $commentText === '') {
            $this->messageManager->addErrorMessage(__('Please fill in all required fields.'));
            return $this->getRedirectToArticle($articleId);
        }

        if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
            $this->messageManager->addErrorMessage(__('Please enter a valid email address.'));
            return $this->getRedirectToArticle($articleId);
        }

        try {
            $comment = $this->commentFactory->create();
            $comment->setArticleId($articleId);
            $comment->setUserName($userName);
            $comment->setUserEmail($userEmail);
            $comment->setComment($commentText);
            $comment->setStatus(\Venbhas\Article\Model\Comment::STATUS_PENDING);
            $comment->save();
            $this->messageManager->addSuccessMessage(__('Your comment has been submitted and is awaiting moderation.'));
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(__('Unable to submit comment. Please try again.'));
        }

        return $this->getRedirectToArticle($articleId);
    }

    /**
     * Get redirect result to article page.
     *
     * @param int $articleId
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    private function getRedirectToArticle(int $articleId)
    {
        $redirect = $this->resultRedirectFactory->create();
        if ($articleId <= 0) {
            $redirect->setPath('*/*/index');
            return $redirect;
        }
        $article = $this->articleFactory->create();
        $this->articleResource->load($article, $articleId);
        if ($article->getId() && $article->getUrlKey()) {
            $storeId = (int) $this->storeManager->getStore()->getId();
            $basePath = trim($this->config->getArticleListRoute($storeId), '/');
            $redirect->setPath($basePath . '/' . $article->getUrlKey());
        } else {
            $redirect->setPath('*/*/index');
        }
        return $redirect;
    }
}
