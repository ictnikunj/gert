<?php declare(strict_types=1);

namespace Ropi\FrontendEditing\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1612270534CreateContentPresetTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1612270534;
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function update(Connection $connection): void
    {
        $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS `ropi_frontend_editing_content_preset` (
  `id`   BINARY(16),
  `created_at` DATETIME(3) NOT NULL,
  `updated_at` DATETIME(3) NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `structure` JSON NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `json.ropi.frontend_editing.content_preset.structure` CHECK (JSON_VALID(`structure`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeUpdate($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}