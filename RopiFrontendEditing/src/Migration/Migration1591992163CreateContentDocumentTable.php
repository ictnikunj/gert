<?php declare(strict_types=1);

namespace Ropi\FrontendEditing\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1591992163CreateContentDocumentTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1591992163;
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function update(Connection $connection): void
    {
        $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS `ropi_frontend_editing_content_document` (
  `id`   BINARY(16),
  `created_at` DATETIME(3) NOT NULL,
  `updated_at` DATETIME(3) NULL,
  `username` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `published` tinyint(1) NOT NULL,
  `sales_channel_id` varchar(32) GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(`structure`, "$.meta.context.salesChannelId"))) VIRTUAL,
  `bundle` varchar(255) GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(`structure`, "$.meta.context.bundle"))) VIRTUAL,
  `controller` varchar(128) GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(`structure`, "$.meta.context.controller"))) VIRTUAL,
  `action` varchar(64) GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(`structure`, "$.meta.context.action"))) VIRTUAL,
  `language_id` varchar(32) GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(`structure`, "$.meta.context.languageId"))) VIRTUAL,
  `subcontext` varchar(32) GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(`structure`, "$.meta.context.subcontext"))) VIRTUAL,
  `structure` JSON NULL,
  PRIMARY KEY (`id`),
  KEY `idx.ropi.frontend_editing.context` (`sales_channel_id`,`bundle`,`controller`,`action`,`language_id`,`subcontext`),
  CONSTRAINT `json.ropi.frontend_editing.structure` CHECK (JSON_VALID(`structure`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeUpdate($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}