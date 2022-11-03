<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1663009925 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1663009925;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
            CREATE TABLE IF NOT EXISTS `acris_download_tab` (
                `id` BINARY(16) NOT NULL,
                `internal_id` VARCHAR(255) NOT NULL,
                `priority` INT(11) NULL DEFAULT 10,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($query);

        $query = <<<SQL
            CREATE TABLE IF NOT EXISTS `acris_download_tab_translation` (
                `display_name` VARCHAR(255) NOT NULL,
                `custom_fields` JSON NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                `acris_download_tab_id` BINARY(16) NOT NULL,
                `language_id` BINARY(16) NOT NULL,
                PRIMARY KEY (`acris_download_tab_id`,`language_id`),
                KEY `fk.acris_download_tab_translation.acris_download_tab_id` (`acris_download_tab_id`),
                KEY `fk.acris_download_tab_translation.language_id` (`language_id`),
                CONSTRAINT `fk.acris_download_tab_translation.acris_download_tab_id` FOREIGN KEY (`acris_download_tab_id`) REFERENCES `acris_download_tab` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.acris_download_tab_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($query);

        $query = <<<SQL
            ALTER TABLE `acris_product_download`
                ADD COLUMN `download_tab_id` BINARY(16) NULL,
                ADD CONSTRAINT `fk.acris_product_download.download_tab_id` FOREIGN KEY (`download_tab_id`) REFERENCES `acris_download_tab` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
SQL;

        $connection->executeStatement($query);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
