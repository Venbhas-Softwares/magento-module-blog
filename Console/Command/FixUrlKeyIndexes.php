<?php
declare(strict_types=1);

namespace Venbhas\Blog\Console\Command;

use Magento\Framework\App\ResourceConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Drop legacy url_key indexes before declarative schema adds unique constraints.
 */
class FixUrlKeyIndexes extends Command
{
    private const LEGACY_INDEXES = [
        'venbhas_article_category' => 'VENBHAS_ARTICLE_CATEGORY_URL_KEY',
        'venbhas_article' => 'VENBHAS_ARTICLE_URL_KEY',
    ];

    /** @var ResourceConnection */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     * @param string|null $name
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        string $name = null
    ) {
        parent::__construct($name);
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->setName('venbhas:blog:fix-url-key-indexes');
        $this->setDescription('Drop legacy url_key indexes so unique constraints can be applied.');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $connection = $this->resourceConnection->getConnection();

        foreach (self::LEGACY_INDEXES as $table => $indexName) {
            if (!$connection->isTableExists($table)) {
                $output->writeln("<comment>Skipped {$table}: table does not exist.</comment>");
                continue;
            }

            $indexes = $connection->getIndexList($table);
            $indexNameUpper = strtoupper($indexName);

            if (!isset($indexes[$indexNameUpper])) {
                $output->writeln("<info>{$table}: legacy index {$indexName} not found (already removed).</info>");
                continue;
            }

            $connection->dropIndex($table, $indexName);
            $output->writeln("<info>{$table}: dropped legacy index {$indexName}.</info>");
        }

        $output->writeln('<info>Done. Now run: bin/magento setup:upgrade</info>');

        return Command::SUCCESS;
    }
}
