<?php
declare(strict_types=1);

namespace Venbhas\Article\Block\Adminhtml\Comment\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Back button for comment edit form.
 */
class BackButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * Get button data.
     *
     * @return array
     */
    public function getButtonData(): array
    {
        return [
            'label' => __('Back'),
            'on_click' => sprintf("location.href = '%s';", $this->getUrl('*/*/')),
            'class' => 'back',
            'sort_order' => 10,
        ];
    }
}
