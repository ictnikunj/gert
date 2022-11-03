<?php

declare(strict_types=1);

namespace Sisi\Search\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1656085043excludefield extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1656085043;
    }

    public function update(Connection $connection): void
    {
        try {
            // implement update
            $connection->executeStatement(
                "ALTER TABLE `s_plugin_sisi_search_es_fields`  ADD `excludesearch` VARCHAR(55)  DEFAULT NULL COLLATE 'utf8mb4_unicode_ci'"
            );
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
        $connection->createQueryBuilder();
    }
}
