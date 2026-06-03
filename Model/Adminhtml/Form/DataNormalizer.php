<?php
declare(strict_types=1);

namespace Venbhas\Blog\Model\Adminhtml\Form;

/**
 * Normalizes admin UI form POST payloads before persistence.
 */
class DataNormalizer
{
    private const META_ROBOTS_CONTAINER = 'container_meta_robots';

    /**
     * Hoist grouped meta robots fields to the root of the request array.
     */
    public static function flattenGroupedFields(array $data): array
    {
        if (isset($data[self::META_ROBOTS_CONTAINER]) && is_array($data[self::META_ROBOTS_CONTAINER])) {
            foreach ($data[self::META_ROBOTS_CONTAINER] as $key => $value) {
                $data[$key] = $value;
            }
            unset($data[self::META_ROBOTS_CONTAINER]);
        }

        $metaRobots = self::findFirstNonEmptyString($data, 'meta_robots');
        if ($metaRobots !== null) {
            $data['meta_robots'] = $metaRobots;
        }

        $useConfigValues = [];
        self::collectNestedValues($data, 'use_config_meta_robots', $useConfigValues);
        if ($useConfigValues !== []) {
            $data['use_config_meta_robots'] = self::resolveUseConfigFlag($useConfigValues);
        }

        return $data;
    }

    /**
     * Apply use-config flag and remove non-persisted UI-only fields.
     */
    public static function resolveMetaRobots(array $data): array
    {
        $metaRobots = self::findFirstNonEmptyString($data, 'meta_robots');

        if ($metaRobots !== null) {
            $data['meta_robots'] = $metaRobots;
        } elseif (self::shouldUseConfigMetaRobots($data)) {
            $data['meta_robots'] = null;
        } else {
            $data['meta_robots'] = null;
        }

        unset($data['use_config_meta_robots'], $data[self::META_ROBOTS_CONTAINER]);

        return $data;
    }

    /**
     * @param array $data
     * @param string $key
     * @param array $values
     */
    private static function collectNestedValues(array $data, string $key, array &$values): void
    {
        if (array_key_exists($key, $data)) {
            $values[] = $data[$key];
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                self::collectNestedValues($value, $key, $values);
            }
        }
    }

    /**
     * @param array $data
     * @param string $key
     */
    private static function findFirstNonEmptyString(array $data, string $key): ?string
    {
        if (array_key_exists($key, $data)) {
            $value = trim((string) $data[$key]);
            if ($value !== '') {
                return $value;
            }
        }

        foreach ($data as $value) {
            if (!is_array($value)) {
                continue;
            }
            $found = self::findFirstNonEmptyString($value, $key);
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    /**
     * @param array<int|string, mixed> $values
     */
    private static function resolveUseConfigFlag(array $values): int
    {
        foreach ($values as $value) {
            if ((int) $value === 0) {
                return 0;
            }
        }

        return 1;
    }

    /**
     * @param array $data
     */
    private static function shouldUseConfigMetaRobots(array $data): bool
    {
        $values = [];
        self::collectNestedValues($data, 'use_config_meta_robots', $values);

        if ($values === []) {
            return true;
        }

        return self::resolveUseConfigFlag($values) === 1;
    }
}
