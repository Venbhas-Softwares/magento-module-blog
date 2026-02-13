<?php
declare(strict_types=1);

namespace Venbhas\Article\Controller\Adminhtml\Comment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultInterface;
use Venbhas\Article\Model\CommentFactory;
use Venbhas\Article\Model\ResourceModel\Comment as CommentResource;

class Save extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Venbhas_Article::comment_save';

    /** @var CommentFactory */
    private $commentFactory;

    /** @var CommentResource */
    private $commentResource;

    /** @var DataPersistorInterface */
    private $dataPersistor;

    private const ALLOWED_FIELDS = [
        'comment_id', 'article_id', 'user_name', 'user_email', 'comment', 'reply', 'status',
    ];

    public function __construct(
        Context $context,
        CommentFactory $commentFactory,
        CommentResource $commentResource,
        DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context);
        $this->commentFactory = $commentFactory;
        $this->commentResource = $commentResource;
        $this->dataPersistor = $dataPersistor;
    }

    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequestData();
        if (!$data || !is_array($data)) {
            $this->messageManager->addErrorMessage(__('Invalid request data.'));
            return $resultRedirect->setPath('*/*/');
        }
        if (!empty($data['data']) && is_array($data['data'])) {
            $data = array_merge($data, $data['data']);
            unset($data['data']);
        }
        $id = (int) ($data['comment_id'] ?? 0);
        $model = $this->commentFactory->create();
        if ($id) {
            $this->commentResource->load($model, $id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This comment no longer exists.'));
                return $resultRedirect->setPath('*/*/');
            }
        }
        $commentData = $this->filterAllowedFields($data);
        if (empty($id) && isset($commentData['comment_id'])) {
            unset($commentData['comment_id']);
        }
        // When editing, do not overwrite user_name, user_email, comment (non-editable)
        if ($id) {
            unset($commentData['user_name'], $commentData['user_email'], $commentData['comment']);
        }
        $model->setData($commentData);
        try {
            $this->commentResource->save($model);
            $this->messageManager->addSuccessMessage(__('You saved the comment.'));
            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['comment_id' => $model->getId()]);
            }
            return $resultRedirect->setPath('*/*/');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->dataPersistor->set('venbhas_article_comment', $data);
            return $resultRedirect->setPath('*/*/edit', $id ? ['comment_id' => $id] : []);
        }
    }

    private function getRequestData(): array
    {
        $request = $this->getRequest();
        $content = $request->getContent();
        if (!empty($content) && $request->getHeader('Content-Type') && strpos((string) $request->getHeader('Content-Type'), 'application/json') !== false) {
            $decoded = json_decode($content, true);
            return is_array($decoded) ? $decoded : [];
        }
        return $request->getPostValue() ?? [];
    }

    private function filterAllowedFields(array $data): array
    {
        $filtered = [];
        foreach (self::ALLOWED_FIELDS as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }
            $filtered[$key] = $data[$key];
        }
        return $filtered;
    }
}
