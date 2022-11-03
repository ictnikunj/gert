<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1600840255 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1600840255;
    }

    public function update(Connection $connection): void
    {
        $this->addColumnJsonToTable($connection, 'acris_product_download', 'language_ids');
    }

    protected function addColumnJsonToTable(Connection $connection, string $entity, string $propertyName): void
    {
        $sql = str_replace(
            ['#table#', '#column#'],
            [$entity, $propertyName],
            "ALTER TABLE `#table#` ADD COLUMN `#column#` JSON NULL"
        );

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
