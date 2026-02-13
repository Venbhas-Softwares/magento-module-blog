<?php
declare(strict_types=1);

namespace Venbhas\Article\Block\Adminhtml\Category\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    public function getButtonData(): array
    {
        $data = [];
        if ($this->getCategoryId()) {
            $data = [
                'label' => __('Delete'),
                'class' => 'delete',
                'on_click' => 'deleteConfirm(\'' . __('Are you sure you want to delete this category?') . '\', \''
                    . $this->getUrl('*/*/delete', ['category_id' => $this->getCategoryId()]) . '\')',
                'sort_order' => 20,
            ];
        }
        return $data;
    }
}
