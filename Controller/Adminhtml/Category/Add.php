<?php
declare(strict_types=1);

namespace Venbhas\Blog\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;

class Add extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Venbhas_Blog::category_save';

    /**
     * @inheritdoc
     */
    public function execute(): ResultInterface
    {
        $parentId = (int) $this->getRequest()->getParam('parent');

        return $this->resultRedirectFactory->create()->setPath(
            '*/*/edit',
            ['parent' => $parentId, 'category_id' => null]
        );
    }
}
