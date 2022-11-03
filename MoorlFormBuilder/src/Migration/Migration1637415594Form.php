<?php declare(strict_types=1);

namespace MoorlFormBuilder\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Migration\InheritanceUpdaterTrait;

class Migration1637415594Form extends MigrationStep
{
    use InheritanceUpdaterTrait;

    public function getCreationTimestamp(): int
    {
        return 1637415594;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE `moorl_form`
ADD `send_copy_type` VARCHAR(255) NOT NULL DEFAULT 'disabled' AFTER `privacy`;
SQL;
        $connection->executeUpdate($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
