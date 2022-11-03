<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\InheritanceUpdaterTrait;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1665321086 extends MigrationStep
{
    use InheritanceUpdaterTrait;

    public function getCreationTimestamp(): int
    {
        return 1665321086;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
CREATE TABLE IF NOT EXISTS `acris_download_tab_rule` (
    `tab_id` BINARY(16) NOT NULL,
    `acris_download_tab_version_id` BINARY(16) NOT NULL,
    `rule_id` BINARY(16) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    PRIMARY KEY (`tab_id`,`rule_id`),
    KEY `fk.acris_download_tab_rule.tab_id` (`tab_id`),
    KEY `fk.acris_download_tab_rule.rule_id` (`rule_id`),
    CONSTRAINT `fk.acris_download_tab_rule.tab_id` FOREIGN KEY (`tab_id`) REFERENCES `acris_download_tab` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk.acris_download_tab_rule.rule_id` FOREIGN KEY (`rule_id`) REFERENCES `rule` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

        $connection->executeStatement($query);
        $this->updateInheritance($connection, 'rule', 'acrisDownloadTabs');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
