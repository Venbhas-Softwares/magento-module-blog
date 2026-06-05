<?php
declare(strict_types=1);

namespace Venbhas\Blog\Model\Adminhtml\Form;

use Venbhas\Blog\Model\Config\Source\MetaRobots;

/**
 * Normalizes admin UI form POST payloads before persistence.
 */
class DataNormalizer
{
    private const META_ROBOTS_CONTAINER = 'container_meta_robots';

    /**
     * Hoist grouped meta robots fields to the root of the request array.
     *
     * @param array $data
     * @return array
     */
    public function flattenGroupedFields(array $data): array
    {
        if (isset($data[self::META_ROBOTS_CONTAINER]) && is_array($data[self::META_ROBOTS_CONTAINER])) {
            foreach ($data[self::META_ROBOTS_CONTAINER] as $key => $value) {
                $data[$key] = $value;
            }
            unset($data[self::META_ROBOTS_CONTAINER]);
        }

        $metaRobots = $this->findFirstMetaRobotsValue($data);
        if ($metaRobots !== null) {
            $data['meta_robots'] = $metaRobots;
        }

        $useConfigValues = [];
        $this->collectNestedValues($data, 'use_config_meta_robots', $useConfigValues);
        if ($useConfigValues !== []) {
            $data['use_config_meta_robots'] = $this->resolveUseConfigFlag($useConfigValues);
        }

        return $data;
    }

    /**
     * Apply use-config flag and remove non-persisted UI-only fields.
     *
     * @param array $data
     * @return array
     */
    public function resolveMetaRobots(array $data): array
    {
        $metaRobots = $this->findFirstMetaRobotsValue($data);

        if ($metaRobots !== null) {
            $data['meta_robots'] = $metaRobots;
        } elseif ($this->shouldUseConfigMetaRobots($data)) {
            $data['meta_robots'] = null;
        } else {
            $data['meta_robots'] = null;
        }

        unset($data['use_config_meta_robots'], $data[self::META_ROBOTS_CONTAINER]);

        return $data;
    }

    /**
     * Collect nested values for a given key from a multi-dimensional array.
     *
     * @param array $data
     * @param string $key
     * @param array $values Collected values (by reference)
     * @return void
     */
    private function collectNestedValues(array $data, string $key, array &$values): void
    {
        if (array_key_exists($key, $data)) {
            $values[] = $data[$key];
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                $this->collectNestedValues($value, $key, $values);
            }
        }
    }

    /**
     * Return the first valid meta robots option id in a nested array.
     *
     * @param array $data
     * @return int|null
     */
    private function findFirstMetaRobotsValue(array $data): ?int
    {
        if (array_key_exists('meta_robots', $data)) {
            $normalized = MetaRobots::normalizeValue($data['meta_robots']);
            if ($normalized !== null) {
                return $normalized;
            }
        }

        foreach ($data as $value) {
            if (!is_array($value)) {
                continue;
            }
            $found = $this->findFirstMetaRobotsValue($value);
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    /**
     * Resolve use-config flag from collected checkbox values.
     *
     * @param array $values Collected checkbox values
     * @return int
     */
    private function resolveUseConfigFlag(array $values): int
    {
        foreach ($values as $value) {
            if ($value === false || $value === 0 || $value === '0') {
                return 0;
            }
        }

        return 1;
    }

    /**
     * Whether meta robots should inherit from store configuration.
     *
     * @param array $data
     * @return bool
     */
    private function shouldUseConfigMetaRobots(array $data): bool
    {
        $values = [];
        $this->collectNestedValues($data, 'use_config_meta_robots', $values);

        if ($values === []) {
            return true;
        }

        return $this->resolveUseConfigFlag($values) === 1;
    }
}
