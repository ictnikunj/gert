<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1646746640 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1646746640;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
        ALTER TABLE `acris_product_download`
            ADD COLUMN `preview_image_enabled` TINYINT(1) NULL DEFAULT '0',
            ADD COLUMN `preview_media_id` BINARY(16) NULL,
            ADD KEY `fk.acris_product_download.preview_media_id` (`preview_media_id`),
            ADD CONSTRAINT `fk.acris_product_download.preview_media_id` FOREIGN KEY (`preview_media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
SQL;
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
