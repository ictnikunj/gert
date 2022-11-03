<?php

namespace Sisi\SisiEsContentSearch6\ESindexing;

use Doctrine\DBAL\Connection;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Sisi\Search\ESindexing\InsertQuery;
use Sisi\Search\ESIndexInterfaces\InterfaceInsertQuery;
use Sisi\SisiEsContentSearch6\Service\MergeMappingService;
use Sisi\SisiEsContentSearch6\Service\MergeSettingsService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Elasticsearch\Client;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Sisi\Search\ESIndexInterfaces\InterfaceInsertProduktDataIndex;
use Sisi\Search\ESindexing\ProduktDataSettings;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Console\Output\OutputInterface;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Sisi\Search\Service\ProgressService;
use Sisi\Search\Service\InsertService;
use Sisi\SisiEsContentSearch6\Service\InsertContentService;
use Sisi\SisiEsContentSearch6\Service\SearchConfigService;
use Sisi\SisiEsContentSearch6\Service\IndexStartService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class InsertProduktDataIndexDecorator implements InterfaceInsertProduktDataIndex
{
    /**
     *
     *
     * @var InterfaceInsertProduktDataIndex
     */
    protected $decorateInterfaceInsertProduktDataIndex;


    /**
     * @var Connection
     */
    protected $connection;


    /**
     *
     * @var ContainerInterface
     */
    protected $container;


    /**
     * @var InterfaceInsertQuery
     */
    protected $insertQuery;

    /**
     *
     * @var SystemConfigService
     */
    protected $config;


    public function __construct(
        InterfaceInsertProduktDataIndex $decorateInterfaceInsertProduktDataIndex,
        Connection $connection,
        ContainerInterface $container,
        InterfaceInsertQuery $insertQuery,
        SystemConfigService $config
    ) {
        $this->decorateInterfaceInsertProduktDataIndex = $decorateInterfaceInsertProduktDataIndex;
        $this->connection = $connection;
        $this->container = $container;
        $this->insertQuery = $insertQuery;
        $this->config = $config;
    }


    /**
     * @param EntitySearchResult $entities
     * @param EntitySearchResult $mappingValues
     * @param Client $client
     * @param string $lanugageId
     * @param Logger $loggingService
     * @param OutputInterface | null $output
     * @param array $parameters
     * @param ContainerInterface $container
     *
     * @return void
     */

    public function setIndex(
        &$entities,
        $mappingValues,
        $client,
        $lanugageId,
        $loggingService,
        $output,
        $parameters,
        $container
    ): void {

        $insertService = new InsertService();
        $insertService->setIndex(
            $entities,
            $mappingValues,
            $client,
            $lanugageId,
            $loggingService,
            $output,
            $this->insertQuery,
            $parameters,
            $container
        );


        if (!array_key_exists('update', $parameters)) {
            $indexStartheanlder = new IndexStartService();
            $indexStartheanlder->startIndex($output, $client, $parameters, $this->container, $this->config);
        }
    }
}
