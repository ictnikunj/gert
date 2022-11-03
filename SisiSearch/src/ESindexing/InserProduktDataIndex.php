<?php

namespace Sisi\Search\ESindexing;

use Doctrine\DBAL\Driver\Connection;
use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Shopware\Core\Content\Media\Pathname\UrlGeneratorInterface;
use Shopware\Core\Content\Product\SalesChannel\Price\ProductPriceDefinitionBuilderInterface;
use Sisi\Search\ESIndexInterfaces\InterfaceInsertProduktDataIndex;
use Symfony\Bridge\Monolog\Logger;
use Elasticsearch\Client;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Symfony\Component\Console\Output\OutputInterface;
use Sisi\Search\Service\ProgressService;
use Sisi\Search\Service\InsertService;
use Sisi\Search\ESindexing\InsertQuery;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InserProduktDataIndex implements InterfaceInsertProduktDataIndex
{

    /**
     * @var InsertQuery
     */
    protected $insertQuery;


    /**
     * InserProduktDataIndex constructor.
     * @param InsertQuery $insertQuery
     */
    public function __construct($insertQuery)
    {
        $this->insertQuery = $insertQuery;
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
    }
}
