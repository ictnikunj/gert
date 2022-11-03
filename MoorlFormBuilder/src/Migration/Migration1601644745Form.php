<?php declare(strict_types=1);

namespace MoorlFormBuilder\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Migration\InheritanceUpdaterTrait;

class Migration1601644745Form extends MigrationStep
{
    use InheritanceUpdaterTrait;

    public function getCreationTimestamp(): int
    {
        return 1601644745;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
UPDATE `moorl_form` SET
`success_message` = NULL,
`submit_text` = NULL;

ALTER TABLE `moorl_form`
CHANGE `success_message` `success_message` JSON NULL AFTER `email_receiver`,
CHANGE `submit_text` `submit_text` JSON NULL AFTER `privacy`;
SQL;

        $connection->executeUpdate($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
