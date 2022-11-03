<?php

namespace Sisi\SisiEsContentSearch6\Commands;

use Sisi\Search\Service\ContextService;
use Sisi\Search\Service\CriteriaService;
use Sisi\Search\Service\InsertTimestampService;
use Sisi\Search\Service\SearchHelpService;
use Sisi\Search\Service\TextService;
use Sisi\Search\Service\TranslationService;
use Sisi\SisiEsContentSearch6\Service\InsertContentService;
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

/**
 *
 * @SuppressWarnings(PHPMD)
 *
 */
class DeleteContentCommand extends Command
{


    protected static $defaultName = 'sisi-Produkt-index:deleteContent';


    /**
     *
     *  @var SystemConfigService
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

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     *
     * @SuppressWarnings(PHPMD)
     *
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Delete Start ..');
        $configEsContent = $this->config->get('SisiEsContentSearch6');
        if (count($configEsContent) > 0) {
            $haendlerInsert = new InsertContentService();
            $criteriaChannel = new Criteria();
            $contextService = new ContextService();
            $heandler = new SearchHelpService();
            $texthaendler = new TextService();
            $criteriaHandler = new CriteriaService();
            $transHaendler = new TranslationService();
            $arguments = $input->getArguments();
            $parameters = $texthaendler->stripOption2($arguments);
            $context = $contextService->getContext();
            $saleschannel = $this->container->get('sales_channel.repository');
            $shop = $parameters['shop'];
            $criteriaHandler->getMergeCriteriaForSalesChannel($criteriaChannel, $shop);
            $salechannelItem = $saleschannel->search($criteriaChannel, $context)->getEntities()->first();
            $channelId = $salechannelItem->getId();
            $config = $this->config->get("SisiSearch.config", $channelId);
            $lanugageValues = $transHaendler->getLanguageId($parameters, $this->connection, $output, $this->loggingService);
            $lanugageId = $transHaendler->chechIsSetLanuageId($lanugageValues, $salechannelItem);
            $lastindexMerker = $heandler->findLast($this->connection, $channelId, $lanugageId, $config);
            $haendlerInsert->deleteEsIndex($config, $lastindexMerker['index']);
        }
        return 0;
    }
}
