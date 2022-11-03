<?php declare(strict_types=1);

namespace MoorlFormBuilder\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Migration\InheritanceUpdaterTrait;

class Migration1630568533Form extends MigrationStep
{
    use InheritanceUpdaterTrait;

    public function getCreationTimestamp(): int
    {
        return 1630568533;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE `moorl_form`
ADD `use_sass_compiler` TINYINT(1) NOT NULL DEFAULT '0' AFTER `privacy`,
ADD `bootstrap_wide_spacing` TINYINT(1) NOT NULL DEFAULT '0' AFTER `privacy`;
SQL;
        $connection->executeUpdate($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
