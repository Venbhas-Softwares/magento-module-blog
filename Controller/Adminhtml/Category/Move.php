<?php
declare(strict_types=1);

namespace Venbhas\Blog\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\LayoutFactory;
use Psr\Log\LoggerInterface;
use Venbhas\Blog\Model\ResourceModel\Category as CategoryResource;

class Move extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Venbhas_Blog::category_save';

    /** @var JsonFactory */
    private $resultJsonFactory;

    /** @var LayoutFactory */
    private $layoutFactory;

    /** @var CategoryResource */
    private $categoryResource;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param LayoutFactory $layoutFactory
     * @param CategoryResource $categoryResource
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        LayoutFactory $layoutFactory,
        CategoryResource $categoryResource,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->layoutFactory = $layoutFactory;
        $this->categoryResource = $categoryResource;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $parentNodeId = (int) $this->getRequest()->getPost('pid', 0);
        $prevNodeId = (int) $this->getRequest()->getPost('aid', 0);
        $categoryId = (int) $this->getRequest()->getPost('id', 0);

        $messagesBlock = $this->layoutFactory->create()->getMessagesBlock();
        $error = false;

        try {
            $this->categoryResource->moveCategory(
                $categoryId,
                $parentNodeId,
                $prevNodeId > 0 ? $prevNodeId : null
            );
            $this->messageManager->addSuccessMessage(__('You moved the category.'));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $error = true;
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $error = true;
            $this->messageManager->addErrorMessage(__('There was a category move error.'));
            $this->logger->critical($e);
        }

        $messagesBlock->setMessages($this->messageManager->getMessages(true));
        $resultJson = $this->resultJsonFactory->create();

        return $resultJson->setData([
            'messages' => $messagesBlock->getGroupedHtml(),
            'error' => $error,
        ]);
    }
}
