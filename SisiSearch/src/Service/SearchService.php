<?php

namespace Sisi\Search\Service;

use Doctrine\DBAL\Connection;
use Elasticsearch\Client;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Sisi\Search\ServicesInterfaces\InterfaceFrontendService;
use Sisi\Search\ServicesInterfaces\InterfaceSearchCategorieService;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SearchService
 * @package Sisi\Search\Service
 *
 * @SuppressWarnings(PHPMD)
 */
class SearchService
{
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
     * @var Client
     */
    protected $client;


    /**
     *
     * @var SystemConfigService
     */
    protected $systemConfigService;


    /**
     *
     * @var Logger
     */
    private $loggingService;


    /**
     *
     * @var array
     */
    private $config;


    /**
     *
     * @var InterfaceSearchCategorieService
     */
    protected $searchCategorieService;


    /**
     * @param SystemConfigService $systemConfigService
     * @param Connection $connection
     * @param ContainerInterface $container
     * @param InterfaceSearchCategorieService $searchCategorieService
     */
    public function __construct(
        SystemConfigService $systemConfigService,
        Connection $connection,
        ContainerInterface $container,
        Logger $loggingService,
        InterfaceSearchCategorieService $searchCategorieService
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->connection = $connection;
        $this->container = $container;
        $this->loggingService = $loggingService;
        $this->searchCategorieService = $searchCategorieService;
    }

    public function search(array $terms, array $systemConfig, SalesChannelContext $saleschannelContext, InterfaceFrontendService $frontendService, ContainerInterface $container): array
    {
        $criteriaForFields = new Criteria();
        $fieldsService = $this->container->get('s_plugin_sisi_search_es_fields.repository');
        $contextService = new ContextService();
        $context = $contextService->getContext();
        $helpService = new SearchHelpService();
        $queryService = new QueryService();
        $heandlerEXService = new ExtSearchService();
        $heandlerExtra = new SearchExtraQueriesService();
        $heandlerIndexService = new CategorieIndexService();
        $saleschannel = $saleschannelContext->getSalesChannel();
        $saleschannelName = $helpService->getChanelName($saleschannel, $this->container);
        $languageId = $saleschannel->getLanguageId();
        $helpService->checkSalesNameIsEmpty($saleschannelName, $this->loggingService);
        $criteriaHandler = new CriteriaService();
        $channelId = $saleschannel->getId();
        $criteriaHandler->getMergeCriteriaForFields($criteriaForFields, $channelId, $languageId);
        $criteriaForFields->addFilter(new EqualsFilter('fieldtype', 'text'));
        $fieldsconfig = $fieldsService->search($criteriaForFields, $context);
        $salechannelID = $saleschannel->getId();
        $index = $helpService->findLast($this->connection, $salechannelID, $languageId, $systemConfig);
        $fields = [];
        $results['producte'] = [];
        $newResult['manufacturer'] = [];
        $match = $queryService->getTheKindOfMatch($systemConfig);
        $terms["product"] = $heandlerEXService->stripUrl($terms["product"], $systemConfig);
        if (sizeOf($fieldsconfig) > 0) {
            $helpService->getFields($terms, $fieldsconfig, $fields, $systemConfig, $match);
        }
        if (array_key_exists('producthits', $systemConfig)) {
            $size = (int)$systemConfig['producthits'];
        }
        if (empty($size) || !is_numeric($size)) {
            $size = 10;
        }
        if ($index == false) {
            return ['producte' => 'noindex', 'manufacturer' => false, 'categories' => false];
        }
        if (array_key_exists('host', $systemConfig)) {
            if ($systemConfig['elasticsearchAktive'] == '1') {
                $heandlerClient = new ClientService();
                $client = $heandlerClient->createClient($systemConfig);
            }
        }
        $fieldsvalues = $heandlerExtra->fixQueryforCategorie($fields, $systemConfig, $match);
        $fields = $fieldsvalues['fields'];
        $from = $helpService->getFromvalue($size, $terms['from']);
        $params = $queryService->getQuery($index, $fields, $systemConfig, $from, $size);
        $paramsManufactory = $params;
        $paramsManufactory = $heandlerEXService->setAndOperator($terms, $paramsManufactory, $fields, $systemConfig, $match);
        $results['categorien'] = [];
        if (count($paramsManufactory) > 0) {
            if (array_key_exists('config', $fieldsvalues) && array_key_exists('categorien', $fieldsvalues)) {
                if ($systemConfig['categorien'] === "6" || $systemConfig['categorien'] === "7") {
                    $paramscat = $params;
                    $paramscat['index'] = $heandlerIndexService->createIndexname($languageId, $salechannelID);
                    $results['categorien'] = $this->searchCategorieService->searchCategorieWithOwnIndex(
                        $systemConfig,
                        $paramscat,
                        $fieldsvalues['categorien'],
                        $client,
                        $terms["product"]
                    );
                } else {
                    $results['categorien'] = $this->searchCategorieService->searchCategorie(
                        $systemConfig,
                        $params,
                        $fieldsvalues['categorien'],
                        $client,
                        $terms["product"]
                    );
                }
            }
            $heandlerExtra->addSuggest($paramsManufactory, $systemConfig, $terms['product']);
            $results['producte'] = $frontendService->search($client, $paramsManufactory, $saleschannelContext, $container);
        }
        $results['manufatory'] = $helpService->sortManufacturer($results['producte']);
        $results['size'] = $size;
        return $results;
    }

    /**
     * @param string $search
     * @param array $systemConfig
     * @param string|null $page
     * @param string|null $languageId
     * @param SalesChannelEntity $saleschannel
     * @return array
     */
    public function searchProducts($search, $systemConfig, $page, $languageId, $saleschannel, SalesChannelContext $saleschannelContext, InterfaceFrontendService $frontendService, ContainerInterface $container): array
    {
        $helpService = new SearchHelpService();
        $salechannelID = $saleschannel->getId();
        $queryService = new QueryService();
        $hanlerExSearchService = new ExtSearchService();
        $heandlerExtra = new SearchExtraQueriesService();
        $criteriaHandler = new CriteriaService();
        $index = $helpService->findLast($this->connection, $salechannelID, $languageId, $systemConfig);
        $criteria = new Criteria();
        $fieldsService = $this->container->get('s_plugin_sisi_search_es_fields.repository');
        $contextService = new ContextService();
        $context = $contextService->getContext();
        $channelId = $saleschannel->getId();
        $criteriaHandler->getMergeCriteriaForFields($criteria, $channelId, $languageId);
        $criteria->addFilter(new EqualsFilter('fieldtype', 'text'));
        $fieldsconfig = $fieldsService->search($criteria, $context);
        $match = $queryService->getTheKindOfMatch($systemConfig);
        $search = $hanlerExSearchService->stripUrl($search, $systemConfig);
        $fields = [];
        $size = 10;
        if (array_key_exists('host', $systemConfig)) {
            if ($systemConfig['elasticsearchAktive'] == '1') {
                $heandlerClient = new ClientService();
                $client = $heandlerClient->createClient($systemConfig);
            }
        }
        if ($index !== false) {
            if (sizeOf($fieldsconfig) > 0) {
                $indexProducts = 0;
                foreach ($fieldsconfig as $row) {
                    $tablename = $row->getTablename();
                    $str = $hanlerExSearchService->strQueryFields($tablename, $systemConfig);
                    $exclude = $row->getExcludesearch();
                    if ($exclude === 'yes') {
                        $str = false;
                    }
                    if ($str) {
                        $shop = $row->getShop();
                        $name = $helpService->setField($row);
                        $queryService->mergeFields($indexProducts, $fields, $match, $search, $row, $name);
                    }
                }
            }
            if (array_key_exists('producthitsSearch', $systemConfig)) {
                $size = (int)$systemConfig['producthitsSearch'];
            }
            $fieldsvalues = $heandlerExtra->fixQueryforCategorie($fields, $systemConfig, $match);
            $fields = $fieldsvalues['fields'];
            $from = $helpService->getFromvalue($size, $page);
            $params = $queryService->getQuery($index, $fields, $systemConfig, $from, $size);
            $heandlerExtra->addSuggest($params, $systemConfig, $search);
            return $frontendService->search($client, $params, $saleschannelContext, $container);
        }
        return [];
    }
}
