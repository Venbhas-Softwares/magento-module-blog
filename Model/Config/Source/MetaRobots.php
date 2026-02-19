<?php
declare(strict_types=1);

namespace Venbhas\Article\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class MetaRobots implements OptionSourceInterface
{
    /**
     * Return meta robots options for select.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'INDEX,FOLLOW', 'label' => __('INDEX, FOLLOW')],
            ['value' => 'NOINDEX,FOLLOW', 'label' => __('NOINDEX, FOLLOW')],
            ['value' => 'INDEX,NOFOLLOW', 'label' => __('INDEX, NOFOLLOW')],
            ['value' => 'NOINDEX,NOFOLLOW', 'label' => __('NOINDEX, NOFOLLOW')],
        ];
    }
}
