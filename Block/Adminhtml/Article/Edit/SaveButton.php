<?php
declare(strict_types=1);

namespace Venbhas\Article\Block\Adminhtml\Article\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Save button for article edit form.
 */
class SaveButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * Get button data.
     *
     * @return array
     */
    public function getButtonData(): array
    {
        return [
            'label' => __('Save'),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => [
                    'buttonAdapter' => [
                        'actions' => [
                            [
                                'targetName' => 'venbhas_article_form.venbhas_article_form',
                                'actionName' => 'save',
                                'params' => [false],
                            ],
                        ],
                    ],
                ],
            ],
            'sort_order' => 90,
        ];
    }
}
