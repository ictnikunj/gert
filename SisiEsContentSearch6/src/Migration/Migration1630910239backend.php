<?php

declare(strict_types=1);

namespace Sisi\SisiEsContentSearch6\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Sisi\Search\Service\ContextService;

class Migration1630910239backend extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1630910239;
    }

    public function update(Connection $connection): void
    {
        /** @phpstan-ignore-next-line */
        $connection->executeStatement(
            "
            CREATE TABLE IF NOT EXISTS `sisi_escontent_fields` (
              `id` BINARY(16) NOT NULL,
              `label` VARCHAR(255) DEFAULT  '',
              `shop` VARCHAR(255) DEFAULT  '',
              `language` VARCHAR(255) DEFAULT  '',
              `display` VARCHAR(255) DEFAULT '',
              `tokenizer` VARCHAR(255) DEFAULT '',
               `minedge` int DEFAULT 3,
                `edge` int DEFAULT 3,
                `filter1` VARCHAR(500)  DEFAULT  '',
               `filter2` VARCHAR(500)  DEFAULT '',
                `filter3` VARCHAR(500)  DEFAULT '',
                `stemming` VARCHAR(500)  DEFAULT '',
                 `stemmingstop` VARCHAR(255)  DEFAULT '',
               `stop` VARCHAR(500)  DEFAULT '',
                `maxhits` int DEFAULT 3,
                `format` VARCHAR(255)  DEFAULT '',
              `pattern` VARCHAR(255)  DEFAULT '',
              `created_at` DATETIME(3) NOT NULL,
              `updated_at` DATETIME(3) NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        "
        );
        $channels = $this->getChannels($connection);
        foreach ($channels as $channel) {
            if ($channel['name'] !== 'Headless' && $channel['name'] !== null) {
                $this->insertField($connection, $channel['name']);
            }
        }
    }

    /**
     * @param Connection $connection
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     */
    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }

    /**
     * @param Connection $connection
     * @param string $name
     * @return void
     *
     */
    private function insertField(Connection $connection, string $name): void
    {
        $contextService = new ContextService();
        $sql = "
          INSERT INTO `sisi_escontent_fields` (`id`, `label`,`shop`,  `tokenizer`,`filter1`,
          `filter2`,`created_at`, `updated_at`)
          VALUES
          (:id,:label,:shop,:tokenizer,:filter1,:filter2, now(), now())";

        /** @phpstan-ignore-next-line */
        $connection->executeStatement(
            $sql,
            [
                'id' => $contextService->getRandom(),
                'label' => 'standard ' . $name,
                'shop' => $name,
                'tokenizer' => 'standard',
                'filter1' => 'lowercase',
                'filter2' => 'autocomplete'
            ]
        );
    }

    /**
     * @param Connection $connection
     * @return mixed[]
     *
     */
    private function getChannels(Connection $connection)
    {
        $handler = $connection->createQueryBuilder()
            ->select(['translation.name'])
            ->from('sales_channel')
            ->leftJoin('sales_channel', 'sales_channel_translation', 'translation', 'sales_channel.id = translation.sales_channel_id')
            ->andWhere("active = 1");
        return $handler->execute()->fetchAll();
    }
}
