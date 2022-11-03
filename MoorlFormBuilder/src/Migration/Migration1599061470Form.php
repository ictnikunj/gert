<?php declare(strict_types=1);

namespace MoorlFormBuilder\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Migration\InheritanceUpdaterTrait;

class Migration1599061470Form extends MigrationStep
{
    use InheritanceUpdaterTrait;

    public function getCreationTimestamp(): int
    {
        return 1599061470;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE `moorl_form`
ADD `insert_newsletter` tinyint(4) NULL AFTER `privacy`,
ADD `insert_history` tinyint(4) NULL AFTER `privacy`,
ADD `submit_text` varchar(255) NULL AFTER `privacy`,
ADD `redirect_params` tinyint(4) NULL AFTER `redirect_to`;
SQL;

        $connection->executeUpdate($sql);

        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `moorl_form_history` (
    `id` BINARY(16) NOT NULL,
    `moorl_form_id` BINARY(16),
    `sales_channel_id` BINARY(16), 
    `email` VARCHAR(255),
    `name` VARCHAR(255),
    `media` JSON NULL,
    `data` JSON NULL,
    `seen` tinyint(4),
    `created_at` DATETIME(3),
    `updated_at` DATETIME(3),
    PRIMARY KEY (`id`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;
SQL;
        $connection->executeUpdate($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
