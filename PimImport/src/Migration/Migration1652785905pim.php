<?php declare(strict_types=1);

namespace PimImport\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1652785905pim extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1652785905;
    }

    public function update(Connection $connection): void
    {
        // implement update
        try {
//            $connection->executeStatement("CREATE TABLE `pim_category` (
//                `id` BINARY(16) NOT NULL,
//                `category_id` BINARY(16) NULL,
//                `category_code` VARCHAR(255) NULL,
//                `sales_channel_id` BINARY(16) NULL,
//                `last_usage_at` DATETIME(3) NULL,
//                `updated_at` DATETIME(3) NULL,
//                `created_at` DATETIME(3) NOT NULL,
//                PRIMARY KEY (`id`)
//            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        } catch (Exception $e) {
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
