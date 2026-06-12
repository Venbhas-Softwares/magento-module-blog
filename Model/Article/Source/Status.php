<?php
declare(strict_types=1);

namespace Venbhas\Blog\Model\Article\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Status implements OptionSourceInterface
{
    public const STATUS_DRAFT = 0;
    public const STATUS_PUBLISHED = 1;

    /**
     * Return status options for select.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        // String values required: JS treats integer 0 as falsy and breaks the admin select binding.
        return [
            ['value' => (string) self::STATUS_DRAFT, 'label' => __('Draft')],
            ['value' => (string) self::STATUS_PUBLISHED, 'label' => __('Published')],
        ];
    }
}
