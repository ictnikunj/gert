<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\InheritanceUpdaterTrait;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1582612684 extends MigrationStep
{
    use InheritanceUpdaterTrait;

    public function getCreationTimestamp(): int
    {
        return 1582612684;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
            CREATE TABLE IF NOT EXISTS `acris_product_download` (
    `id` BINARY(16) NOT NULL,
    `media_id` BINARY(16) NOT NULL,
    `product_id` BINARY(16) NOT NULL,
    `product_version_id` BINARY(16) NOT NULL,
    `position` INT(11) NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    KEY `fk.acris_product_download.media_id` (`media_id`),
    KEY `fk.acris_product_download.product_id` (`product_id`,`product_version_id`),
    CONSTRAINT `fk.acris_product_download.media_id` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk.acris_product_download.product_id` FOREIGN KEY (`product_id`,`product_version_id`) REFERENCES `product` (`id`,`version_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($query);

        $query = <<<SQL
            CREATE TABLE IF NOT EXISTS `acris_product_download_language` (
    `download_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    PRIMARY KEY (`download_id`,`language_id`),
    KEY `fk.acris_product_download_language.download_id` (`download_id`),
    KEY `fk.acris_product_download_language.language_id` (`language_id`),
    CONSTRAINT `fk.acris_product_download_language.download_id` FOREIGN KEY (`download_id`) REFERENCES `acris_product_download` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk.acris_product_download_language.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($query);

        $query = <<<SQL
                CREATE TABLE IF NOT EXISTS `acris_product_download_translation` (
    `title` VARCHAR(255) NOT NULL,
    `description` LONGTEXT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    `acris_product_download_id` BINARY(16) NOT NULL,
    `language_id` BINARY(16) NOT NULL,
    PRIMARY KEY (`acris_product_download_id`,`language_id`),
    KEY `fk.acris_product_download_translation.acris_product_download_id` (`acris_product_download_id`),
    KEY `fk.acris_product_download_translation.language_id` (`language_id`),
    CONSTRAINT `fk.acris_product_download_translation.acris_product_download_id` FOREIGN KEY (`acris_product_download_id`) REFERENCES `acris_product_download` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk.acris_product_download_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($query);

        $this->updateInheritance($connection, 'product', 'acrisDownloads');
        $this->updateInheritance($connection, 'media', 'acrisDownloads');
        $this->updateInheritance($connection, 'language', 'acrisDownloads');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
