<?php
declare(strict_types=1);

namespace Venbhas\Article\Model\Comment\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Venbhas\Article\Model\Comment;

class Status implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => Comment::STATUS_PENDING, 'label' => __('Pending')],
            ['value' => Comment::STATUS_APPROVED, 'label' => __('Approved')],
            ['value' => Comment::STATUS_REJECTED, 'label' => __('Rejected')],
        ];
    }
}
