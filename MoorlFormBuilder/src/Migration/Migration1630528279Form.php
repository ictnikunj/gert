<?php declare(strict_types=1);

namespace MoorlFormBuilder\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Migration\InheritanceUpdaterTrait;

class Migration1630528279Form extends MigrationStep
{
    use InheritanceUpdaterTrait;

    public function getCreationTimestamp(): int
    {
        return 1630528279;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE `moorl_form`
ADD `customer_mail_template_id` binary(16) NULL AFTER `mail_template_id`,
ADD `file_upload_method` VARCHAR(255) NULL AFTER `privacy`;
SQL;
        $connection->executeUpdate($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
