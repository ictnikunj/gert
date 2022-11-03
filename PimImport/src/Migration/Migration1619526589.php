<?php declare(strict_types=1);

namespace PimImport\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1619526589 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1619526589;
    }

    public function update(Connection $connection): void
    {
        // implement update
        try {
////            $connection->executeStatement('
////            CREATE TABLE `pim_product` (
////                `id` BINARY(16) NOT NULL,
////                `product_number` VARCHAR(255) NULL,
////                `last_usage_at` DATETIME(3) NULL,
////                `last_related_cross_usage_at` DATETIME(3) NULL,
////                `last_productpart_cross_usage_at` DATETIME(3) NULL,
////                `last_addon_cross_usage_at` DATETIME(3) NULL,
////                `updated_at` DATETIME(3) NULL,
////                `created_at` DATETIME(3) NOT NULL,
////                PRIMARY KEY (`id`)
////                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
//            ');
        } catch (Exception $e) {
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
