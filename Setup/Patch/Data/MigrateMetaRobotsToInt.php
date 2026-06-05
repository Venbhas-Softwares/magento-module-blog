<?php
declare(strict_types=1);

namespace Venbhas\Blog\Setup\Patch\Data;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Venbhas\Blog\Model\Config\Source\MetaRobots;

/**
 * Convert legacy text meta_robots values to integer option ids.
 */
class MigrateMetaRobotsToInt implements DataPatchInterface
{
    private const CONFIG_PATHS = [
        'venbhas_blog/meta_robots/category',
        'venbhas_blog/meta_robots/post',
    ];

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
        $this->moduleDataSetup->getConnection()->startSetup();

        $this->migrateTable('venbhas_article_category');
        $this->migrateTable('venbhas_article');
        $this->migrateConfigValues();

        $this->moduleDataSetup->getConnection()->endSetup();
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

    /**
     * Migrate entity table values and ensure column type is smallint.
     *
     * @param string $tableCode
     * @return void
     */
    private function migrateTable(string $tableCode): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $tableName = $this->moduleDataSetup->getTable($tableCode);

        if (!$connection->isTableExists($tableName)
            || !$connection->tableColumnExists($tableName, 'meta_robots')
        ) {
            return;
        }

        $columnType = strtolower((string) $connection->describeTable($tableName)['meta_robots']['DATA_TYPE']);
        if (in_array($columnType, ['varchar', 'text', 'tinytext', 'mediumtext', 'longtext'], true)) {
            $select = $connection->select()
                ->from($tableName, ['entity_id' => $this->getPrimaryKeyColumn($tableCode), 'meta_robots'])
                ->where('meta_robots IS NOT NULL')
                ->where("TRIM(meta_robots) <> ''");

            foreach ($connection->fetchAll($select) as $row) {
                $normalized = MetaRobots::normalizeValue($row['meta_robots']);
                $connection->update(
                    $tableName,
                    ['meta_robots' => $normalized],
                    [$this->getPrimaryKeyColumn($tableCode) . ' = ?' => (int) $row['entity_id']]
                );
            }

            $connection->changeColumn(
                $tableName,
                'meta_robots',
                'meta_robots',
                [
                    'type' => Table::TYPE_SMALLINT,
                    'unsigned' => true,
                    'nullable' => true,
                    'comment' => 'Meta Robots',
                ]
            );

            return;
        }

        $select = $connection->select()
            ->from($tableName, ['entity_id' => $this->getPrimaryKeyColumn($tableCode), 'meta_robots'])
            ->where('meta_robots = ?', 0);

        foreach ($connection->fetchAll($select) as $row) {
            $connection->update(
                $tableName,
                ['meta_robots' => null],
                [$this->getPrimaryKeyColumn($tableCode) . ' = ?' => (int) $row['entity_id']]
            );
        }
    }

    /**
     * Migrate store configuration values to integer option ids.
     *
     * @return void
     */
    private function migrateConfigValues(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $configTable = $this->moduleDataSetup->getTable('core_config_data');

        $select = $connection->select()
            ->from($configTable, ['config_id', 'value'])
            ->where('path IN (?)', self::CONFIG_PATHS);

        foreach ($connection->fetchAll($select) as $row) {
            $normalized = MetaRobots::normalizeValue($row['value']);
            if ($normalized === null) {
                continue;
            }

            $connection->update(
                $configTable,
                ['value' => (string) $normalized],
                ['config_id = ?' => (int) $row['config_id']]
            );
        }
    }

    /**
     * @param string $tableCode
     * @return string
     */
    private function getPrimaryKeyColumn(string $tableCode): string
    {
        return $tableCode === 'venbhas_article' ? 'article_id' : 'category_id';
    }
}
