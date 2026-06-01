<?php
declare(strict_types=1);

namespace Venbhas\Blog\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Initialize path, level, and children_count for existing categories.
 */
class InitializeCategoryTree implements DataPatchInterface
{
    /** @var ModuleDataSetupInterface */
    private $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * @inheritdoc
     */
    public function apply(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable('venbhas_article_category');

        $select = $connection->select()
            ->from($table, ['category_id', 'parent_id', 'path', 'level'])
            ->order('level ASC')
            ->order('category_id ASC');
        $rows = $connection->fetchAll($select);

        if ($rows === []) {
            return;
        }

        $byId = [];
        foreach ($rows as $row) {
            $byId[(int) $row['category_id']] = $row;
        }

        foreach ($rows as $row) {
            $categoryId = (int) $row['category_id'];
            $parentId = (int) ($row['parent_id'] ?? 0);
            $path = trim((string) ($row['path'] ?? ''));
            $level = (int) ($row['level'] ?? 0);

            if ($path !== '' && $level > 0) {
                continue;
            }

            if ($parentId > 0 && isset($byId[$parentId])) {
                $parentPath = trim((string) ($byId[$parentId]['path'] ?? ''));
                if ($parentPath === '') {
                    $parentPath = (string) $parentId;
                }
                $path = $parentPath . '/' . $categoryId;
                $level = (int) ($byId[$parentId]['level'] ?? 1) + 1;
            } else {
                $parentId = 0;
                $path = (string) $categoryId;
                $level = 1;
            }

            $connection->update(
                $table,
                [
                    'parent_id' => $parentId,
                    'path' => $path,
                    'level' => $level,
                ],
                ['category_id = ?' => $categoryId]
            );

            $byId[$categoryId]['parent_id'] = $parentId;
            $byId[$categoryId]['path'] = $path;
            $byId[$categoryId]['level'] = $level;
        }

        $childrenCount = [];
        foreach ($byId as $row) {
            $parentId = (int) ($row['parent_id'] ?? 0);
            if ($parentId > 0) {
                $childrenCount[$parentId] = ($childrenCount[$parentId] ?? 0) + 1;
            }
        }

        foreach ($childrenCount as $parentId => $count) {
            $connection->update(
                $table,
                ['children_count' => $count],
                ['category_id = ?' => $parentId]
            );
        }
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
