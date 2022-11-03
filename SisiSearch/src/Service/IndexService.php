<?php

namespace Sisi\Search\Service;

use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Console\Output\OutputInterface;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerEntity;

class IndexService
{

    /**
     * @param ProductManufacturerEntity|null|false $manufacturers
     * @param array $fields
     * @return bool
     */
    public function checkManufacturer($manufacturers, &$fields)
    {
        if ($manufacturers !== null && $manufacturers !== false) {
            if (method_exists($manufacturers, 'getId')) {
                $fields['manufacturer_id'] = trim($manufacturers->getId());
                return true;
            }
        }
        return false;
    }
    /**
     * @param Connection $connection
     * @param string $lanuageId
     * @return false|mixed
     * @throws \Doctrine\DBAL\Exception
     */
    public function getLanuageNameById($connection, $lanuageId)
    {
        $context = new ContextService();
        $lanuageId = $context->getFromHexToBytes($lanuageId);
        $query = $connection->createQueryBuilder()
            ->select('name')
            ->from('language')
            ->andWhere('id = :id')
            ->setParameters(['id' => $lanuageId]);
        return $query->execute()->fetchColumn();
    }

    /**
     * @param Connection $connection
     * @return mixed[]
     */

    public function getIndexes(connection $connection)
    {
        $query = $connection->createQueryBuilder()
            ->select('HEX(`id`) AS `id`, time')
            ->from(' s_plugin_sisi_search_es_index')
            ->orderBy('time', 'ASC');
        return $query->execute()->fetchAll();
    }

    public function checkChannelId(array $salechannelItem, Logger $loggingService): bool
    {
        if (count($salechannelItem) == 0) {
            $loggingService->log('100', 'Channel not found ');
            echo "Channel not found\n";
            return false;
        }
        return true;
    }

    public function checkshop(string $shop, Logger $loggingService): bool
    {
        if (empty($shop)) {
            $loggingService->log('100', 'Channel not found');
            return false;
        }
        return true;
    }

    /**
     * @param array $parameters
     * @param Connection $connection
     * @param ContextService $contextService
     * @param int $time
     * @param string $channelId
     * @param string $token
     * @param string $lanugageId
     * @param OutputInterface|null $output
     *
     * @return int
     * @throws \Doctrine\DBAL\Exception
     */
    public function indexFinsih(
        $parameters,
        $connection,
        $contextService,
        $time,
        $channelId,
        $token,
        $lanugageId,
        $output
    ): int {
        $return = 0;
        if (!array_key_exists('update', $parameters)) {
            $connection->insert(
                's_plugin_sisi_search_es_index',
                [
                     'id' => $contextService->getRandom(),
                    '`entity`' => 'product',
                    '`index`' => $parameters['esIndex'],
                    '`time`' => $time,
                    '`shop`' => $channelId,
                    '`token`' => $token,
                    '`language`' => (string)$lanugageId
                ]
            );
            $return = $time;
        }
        if (array_key_exists('backend', $parameters) && $output !== null) {
            $output->writeln("The indexing process is finished \n");
        }
        return $return;
    }
}
