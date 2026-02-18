<?php
declare(strict_types=1);

namespace Venbhas\Article\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class SortOrder implements OptionSourceInterface
{
    public const NEW_TO_OLD = 'new_to_old';
    public const OLD_TO_NEW = 'old_to_new';
    public const A_TO_Z = 'a_to_z';
    public const Z_TO_A = 'z_to_a';

    public function toOptionArray(): array
    {
        return [
            ['value' => self::NEW_TO_OLD, 'label' => __('New to Old')],
            ['value' => self::OLD_TO_NEW, 'label' => __('Old to New')],
            ['value' => self::A_TO_Z, 'label' => __('A to Z')],
            ['value' => self::Z_TO_A, 'label' => __('Z to A')],
        ];
    }
}
