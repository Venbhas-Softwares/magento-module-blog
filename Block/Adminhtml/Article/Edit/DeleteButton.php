<?php
declare(strict_types=1);

namespace Venbhas\Article\Block\Adminhtml\Article\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Delete button for article edit form.
 */
class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * Get button data.
     *
     * @return array
     */
    public function getButtonData(): array
    {
        $data = [];
        if ($this->getArticleId()) {
            $data = [
                'label' => __('Delete'),
                'class' => 'delete',
                'on_click' => 'deleteConfirm(\'' . __('Are you sure you want to delete this article?') . '\', \''
                    . $this->getUrl('*/*/delete', ['article_id' => $this->getArticleId()]) . '\')',
                'sort_order' => 20,
            ];
        }
        return $data;
    }
}
