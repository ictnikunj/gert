<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\InheritanceUpdaterTrait;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1649064927 extends MigrationStep
{
    use InheritanceUpdaterTrait;

    public function getCreationTimestamp(): int
    {
        return 1649064927;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
            CREATE TABLE IF NOT EXISTS `acris_product_link` (
                `id` BINARY(16) NOT NULL,
                `product_id` BINARY(16) NOT NULL,
                `product_version_id` BINARY(16) NOT NULL,
                `language_ids` JSON NULL,
                `position` INT(11) NULL,
                `url` VARCHAR(255) NOT NULL,
                `link_target` TINYINT(1) NULL DEFAULT '1',
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `json.acris_product_link.language_ids` CHECK (JSON_VALID(`language_ids`)),
                KEY `fk.acris_product_link.product_id` (`product_id`,`product_version_id`),
                CONSTRAINT `fk.acris_product_link.product_id` FOREIGN KEY (`product_id`,`product_version_id`) REFERENCES `product` (`id`,`version_id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($query);

        $query = <<<SQL
            CREATE TABLE IF NOT EXISTS `acris_product_link_language` (
                `link_id` BINARY(16) NOT NULL,
                `language_id` BINARY(16) NOT NULL,
                `created_at` DATETIME(3) NOT NULL,
                PRIMARY KEY (`link_id`,`language_id`),
                KEY `fk.acris_product_link_language.link_id` (`link_id`),
                KEY `fk.acris_product_link_language.language_id` (`language_id`),
                CONSTRAINT `fk.acris_product_link_language.link_id` FOREIGN KEY (`link_id`) REFERENCES `acris_product_link` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.acris_product_link_language.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($query);

        $query = <<<SQL
            CREATE TABLE IF NOT EXISTS `acris_product_link_translation` (
                `title` VARCHAR(255) NULL,
                `description` LONGTEXT NULL,
                `custom_fields` JSON NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                `acris_product_link_id` BINARY(16) NOT NULL,
                `language_id` BINARY(16) NOT NULL,
                PRIMARY KEY (`acris_product_link_id`,`language_id`),
                CONSTRAINT `json.acris_product_link_translation.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
                KEY `fk.acris_product_link_translation.acris_product_link_id` (`acris_product_link_id`),
                KEY `fk.acris_product_link_translation.language_id` (`language_id`),
                CONSTRAINT `fk.acris_product_link_translation.acris_product_link_id` FOREIGN KEY (`acris_product_link_id`) REFERENCES `acris_product_link` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.acris_product_link_translation.language_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($query);

        $this->updateInheritance($connection, 'product', 'acrisLinks');
        $this->updateInheritance($connection, 'language', 'acrisLinks');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
