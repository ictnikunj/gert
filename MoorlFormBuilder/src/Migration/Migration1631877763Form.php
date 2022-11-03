<?php declare(strict_types=1);

namespace MoorlFormBuilder\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Migration\InheritanceUpdaterTrait;

class Migration1631877763Form extends MigrationStep
{
    use InheritanceUpdaterTrait;

    public function getCreationTimestamp(): int
    {
        return 1631877763;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE `moorl_form`
ADD `redirect_conditions` json NULL AFTER `redirect_params`;
SQL;
        $connection->executeUpdate($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
