<?php
declare(strict_types=1);

namespace Venbhas\Blog\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class MetaRobots implements OptionSourceInterface
{
    public const INDEX_FOLLOW = 1;
    public const NOINDEX_FOLLOW = 2;
    public const INDEX_NOFOLLOW = 3;
    public const NOINDEX_NOFOLLOW = 4;

    private const DIRECTIVES = [
        self::INDEX_FOLLOW => 'INDEX,FOLLOW',
        self::NOINDEX_FOLLOW => 'NOINDEX,FOLLOW',
        self::INDEX_NOFOLLOW => 'INDEX,NOFOLLOW',
        self::NOINDEX_NOFOLLOW => 'NOINDEX,NOFOLLOW',
    ];

    /**
     * Return meta robots options for select.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::INDEX_FOLLOW, 'label' => __('INDEX, FOLLOW')],
            ['value' => self::NOINDEX_FOLLOW, 'label' => __('NOINDEX, FOLLOW')],
            ['value' => self::INDEX_NOFOLLOW, 'label' => __('INDEX, NOFOLLOW')],
            ['value' => self::NOINDEX_NOFOLLOW, 'label' => __('NOINDEX, NOFOLLOW')],
        ];
    }

    /**
     * Convert stored option id to robots meta directive.
     *
     * @param int|string|null $value
     * @return string
     */
    public static function toDirective($value): string
    {
        $id = (int) $value;

        return self::DIRECTIVES[$id] ?? self::DIRECTIVES[self::INDEX_FOLLOW];
    }

    /**
     * Normalize legacy text or numeric values to option id.
     *
     * @param mixed $value
     * @return int|null
     */
    public static function normalizeValue($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $id = (int) $value;

            return isset(self::DIRECTIVES[$id]) ? $id : null;
        }

        $legacyMap = array_flip(self::DIRECTIVES);
        $text = strtoupper(str_replace(' ', '', trim((string) $value)));

        return $legacyMap[$text] ?? null;
    }
}
