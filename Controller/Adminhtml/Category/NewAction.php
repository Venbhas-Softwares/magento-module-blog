<?php
declare(strict_types=1);

namespace Venbhas\Article\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;

class NewAction extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Venbhas_Article::category_save';

    /**
     * Execute action.
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        return $this->resultRedirectFactory->create()->setPath('*/*/edit');
    }
}
