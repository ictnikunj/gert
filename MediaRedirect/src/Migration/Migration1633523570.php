<?php declare(strict_types=1);

namespace MediaRedirect\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1633523570 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1633523570;
    }

    public function update(Connection $connection): void
    {
        try {
            $connection->executeStatement('
                CREATE TABLE IF NOT EXISTS `ict_media_redirect` (
                    `id` BINARY(16) NOT NULL,
                    `url` LONGTEXT NULL,
                    `media_id` BINARY(16) NULL,
                    `created_at` DATETIME(3) NOT NULL,
                    `updated_at` DATETIME(3) NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ');
        } catch (Exception $e) {
        }
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
