<?php
declare(strict_types=1);

namespace Venbhas\Article\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Ui\Component\MassAction\Filter;
use Venbhas\Article\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;

class MassDisable extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Venbhas_Article::category_save';

    /** @var Filter */
    private $filter;

    /** @var CategoryCollectionFactory */
    private $collectionFactory;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param Filter $filter
     * @param CategoryCollectionFactory $collectionFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CategoryCollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Execute action.
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $size = 0;
            foreach ($collection->getItems() as $category) {
                $category->setStatus(0);
                $category->save();
                $size++;
            }
            $this->messageManager->addSuccessMessage(__('A total of %1 category(ies) have been disabled.', $size));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        return $resultRedirect->setPath('*/*/');
    }
}
