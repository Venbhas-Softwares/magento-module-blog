<?php
declare(strict_types=1);

namespace Venbhas\Blog\Controller\Adminhtml\Article;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Ui\Component\MassAction\Filter;
use Venbhas\Blog\Model\Article\Source\Status as ArticleStatus;
use Venbhas\Blog\Model\ResourceModel\Article as ArticleResource;
use Venbhas\Blog\Model\ResourceModel\Article\CollectionFactory as ArticleCollectionFactory;

class MassStatus extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Venbhas_Blog::article_save';

    /** @var Filter */
    private $filter;

    /** @var ArticleCollectionFactory */
    private $collectionFactory;

    /** @var ArticleResource */
    private $articleResource;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param ArticleCollectionFactory $collectionFactory
     * @param ArticleResource $articleResource
     */
    public function __construct(
        Context $context,
        Filter $filter,
        ArticleCollectionFactory $collectionFactory,
        ArticleResource $articleResource
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->articleResource = $articleResource;
    }

    /**
     * Mass-update article status from admin grid.
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        $status = (int) $this->getRequest()->getParam('status');
        $allowed = [ArticleStatus::STATUS_DRAFT, ArticleStatus::STATUS_PUBLISHED];
        if (!in_array($status, $allowed, true)) {
            $this->messageManager->addErrorMessage(__('Invalid status value.'));
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $updated = 0;

            foreach ($collection as $article) {
                $article->setData('status', $status);
                $this->articleResource->save($article);
                $updated++;
            }

            $label = $status === ArticleStatus::STATUS_PUBLISHED
                ? __('Published')
                : __('Draft');

            $this->messageManager->addSuccessMessage(
                __('A total of %1 article(s) have been set to %2.', $updated, $label)
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect->setPath('*/*/');
    }
}
