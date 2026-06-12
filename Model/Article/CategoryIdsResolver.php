<?php
declare(strict_types=1);

namespace Venbhas\Blog\Model\Article;

/**
 * Normalizes category_ids from admin UI form POST/JSON payloads.
 */
class CategoryIdsResolver
{
    /**
     * Extract unique category ids from request/form data.
     *
     * @param array $data
     * @return int[]
     */
    public function resolve(array $data): array
    {
        $raw = $this->extractRaw($data);

        return $this->normalize($raw);
    }

    /**
     * Read raw category id value from nested form data.
     *
     * @param array $data
     * @return mixed
     */
    private function extractRaw(array $data)
    {
        foreach (['category_ids', 'category_id'] as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== '' && $data[$key] !== null) {
                return $data[$key];
            }
        }

        if (!empty($data['data']) && is_array($data['data'])) {
            $nested = $this->extractRaw($data['data']);
            if ($nested !== null && $nested !== '' && $nested !== []) {
                return $nested;
            }
        }

        if (!empty($data['general']) && is_array($data['general'])) {
            $nested = $this->extractRaw($data['general']);
            if ($nested !== null && $nested !== '' && $nested !== []) {
                return $nested;
            }
        }

        return $this->findNested($data);
    }

    /**
     * Search nested arrays for category id fields.
     *
     * @param array $data
     * @return mixed
     */
    private function findNested(array $data)
    {
        foreach ($data as $key => $value) {
            if ($key === 'category_ids' || $key === 'category_id') {
                if ($value !== '' && $value !== null) {
                    return $value;
                }
            }
            if (is_array($value)) {
                $found = $this->findNested($value);
                if ($found !== null && $found !== '' && $found !== []) {
                    return $found;
                }
            }
        }

        return [];
    }

    /**
     * Normalize mixed category id input to unique integers.
     *
     * @param mixed $raw
     * @return int[]
     */
    private function normalize($raw): array
    {
        if ($raw === null || $raw === '' || $raw === []) {
            return [];
        }

        if (is_string($raw)) {
            $trimmed = trim($raw);
            if ($trimmed === '') {
                return [];
            }
            if ($trimmed[0] === '[') {
                $decoded = json_decode($trimmed, true);
                if (is_array($decoded)) {
                    return $this->normalize($decoded);
                }
            }

            return $this->normalize(explode(',', $trimmed));
        }

        if (!is_array($raw)) {
            $id = (int) $raw;

            return $id > 0 ? [$id] : [];
        }

        $ids = [];
        foreach ($raw as $item) {
            if (is_array($item)) {
                $item = $item['value'] ?? $item['category_id'] ?? $item['id'] ?? reset($item);
            }
            $id = (int) $item;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }
}
