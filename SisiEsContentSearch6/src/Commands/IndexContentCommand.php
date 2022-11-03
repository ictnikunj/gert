<?php

namespace Sisi\SisiEsContentSearch6\Commands;

use Sisi\Search\Service\ClientService;
use Sisi\Search\Service\ContextService;
use Sisi\Search\Service\CriteriaService;
use Sisi\Search\Service\IndexService;
use Sisi\Search\Service\InsertTimestampService;
use Sisi\Search\Service\SearchHelpService;
use Sisi\Search\Service\TextService;
use Sisi\Search\Service\TranslationService;
use Sisi\SisiEsContentSearch6\Service\IndexStartService;
use Symfony\Bridge\Monolog\Logger;
use Sisi\Search\ESindexing\ProduktDataIndexer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Sisi\Search\Service\ProductService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Doctrine\DBAL\Connection;
use Elasticsearch\ClientBuilder;
use Shopware\Elasticsearch\Framework\ElasticsearchHelper;
use Shopware\Core\Framework\Uuid\Uuid;
use Sisi\Search\Service\SearchService;
use Sisi\Search\ESindexing\ProduktDataSettings;
use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Shopware\Core\Content\Product\SalesChannel\Price\ProductPriceDefinitionBuilderInterface;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Sisi\SisiEsContentSearch6\Service\InsertContentService;

/**
 *
 * @SuppressWarnings(PHPMD)
 */
class IndexContentCommand extends Command
{


    protected static $defaultName = 'sisi-Produkt-index:indexContent';


    /**
     *
     * @var SystemConfigService
     */
    protected $config;

    /**
     *
     * @var ContainerInterface
     */
    protected $container;


    /**
     * @var Connection
     */
    protected $connection;


    /**
     * @var Logger
     */
    protected $loggingService;


    public function __construct(SystemConfigService $config, ContainerInterface $container, Connection $connection, Logger $loggingService)
    {
        parent::__construct();
        $this->config = $config;
        $this->container = $container;
        $this->connection = $connection;
        $this->loggingService = $loggingService;
    }

    protected function configure(): void
    {
        $this->addArgument('shop', InputArgument::REQUIRED, 'shop Channel');
        $this->addArgument('language', InputArgument::OPTIONAL, 'This parameter is necessary when you want use not the default language');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $arguments = $input->getArguments();
        $texthaendler = new TextService();
        $contextService = new ContextService();
        $criteriaHandler = new CriteriaService();
        $indexStartheanlder = new IndexStartService();
        $transHaendler = new TranslationService();
        $timeHaendler = new InsertTimestampService();
        $heandler = new SearchHelpService();
        $haendlerInsert = new InsertContentService();
        $str = true;
        $parameters = $texthaendler->stripOption2($arguments);
        $saleschannel = $this->container->get('sales_channel.repository');
        $shop = $parameters['shop'];
        $criteriaChannel = new Criteria();
        $context = $contextService->getContext();
        $criteriaHandler->getMergeCriteriaForSalesChannel($criteriaChannel, $shop);
        $salechannelItem = $saleschannel->search($criteriaChannel, $context)->getEntities()->first();
        $channelId = $salechannelItem->getId();
        $config = $this->config->get("SisiSearch.config", $channelId);
        $lanugageValues = $transHaendler->getLanguageId($parameters, $this->connection, $output, $this->loggingService);
        $lanugageId = $transHaendler->chechIsSetLanuageId($lanugageValues, $salechannelItem);
        $lastindexMerker = $heandler->findLast($this->connection, $channelId, $lanugageId, $config);
        if ($lastindexMerker === false) {
            $this->loggingService->log('100', "No Index found \n");
            $output->writeln("No Index found");
            $str = false;
        }
        if ($str) {
            $parameters['language_id'] = $lanugageId;
            $parameters['esIndex'] = $lastindexMerker['index'];
            $haendlerInsert->deleteEsIndex($config, $lastindexMerker['index']);
            $heandlerClient = new ClientService();
            $client = $heandlerClient->createClient($config);
            $indexStartheanlder->startIndex($output, $client, $parameters, $this->container, $this->config);
        }
        return 0;
    }

    /**
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     *
     * @param array $salechannelItem
     * @return int|string|null
     */
    private function getArrayFirst(array $salechannelItem)
    {
        foreach ($salechannelItem as $key => $unused) {
            return $key;
        }
        return null;
    }
}
