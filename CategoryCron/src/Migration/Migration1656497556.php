<?php declare(strict_types=1);

namespace CategoryCron\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1656497556 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1656497556;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement("CREATE TABLE `category_cron_saleschannel` (
    `id` BINARY(16) NOT NULL,
    `sales_channel_id` BINARY(16) NULL,
    `last_usage_at` DATETIME(3) NULL,
    `updated_at` DATETIME(3) NULL,
    `created_at` DATETIME(3) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
