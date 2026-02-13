<?php
declare(strict_types=1);

namespace Venbhas\Article\Model\Article\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Status implements OptionSourceInterface
{
    const STATUS_DRAFT = 0;
    const STATUS_PUBLISHED = 1;

    public function toOptionArray(): array
    {
        return [
            ['value' => self::STATUS_DRAFT, 'label' => __('Draft')],
            ['value' => self::STATUS_PUBLISHED, 'label' => __('Published')],
        ];
    }
}
