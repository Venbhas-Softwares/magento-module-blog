<?php
declare(strict_types=1);

namespace Venbhas\Article\Block\Adminhtml\Comment\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    public function getButtonData(): array
    {
        $data = [];
        if ($this->getCommentId()) {
            $data = [
                'label' => __('Delete'),
                'class' => 'delete',
                'on_click' => 'deleteConfirm(\'' . __('Are you sure you want to delete this comment?') . '\', \''
                    . $this->getUrl('*/*/delete', ['comment_id' => $this->getCommentId()]) . '\')',
                'sort_order' => 20,
            ];
        }
        return $data;
    }
}
