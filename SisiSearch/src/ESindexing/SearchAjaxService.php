<?php

namespace Sisi\Search\ESindexing;

use Doctrine\DBAL\Connection;
use Elasticsearch\Client;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Sisi\Search\ESIndexInterfaces\InterSearchAjaxService;
use Sisi\Search\Service\ClientService;
use Sisi\Search\Service\ContextService;
use Sisi\Search\Service\ExtSearchService;
use Sisi\Search\Service\QueryService;
use Sisi\Search\Service\SearchExtraQueriesService;
use Sisi\Search\Service\SearchHelpService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SearchAjaxService implements InterSearchAjaxService
{
    /**
     * @param string $term
     * @param array|null $properties
     * @param array|string|null $manufactoryIds
     * @param array $config
     * @param SalesChannelContext $saleschannelContext
     * @param Connection $connection
     * @param array $getParams
     * @param ContainerInterface $container
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @return array
     */
    public function searchProducts(
        $term,
        $properties,
        $manufactoryIds,
        $config,
        $saleschannelContext,
        $connection,
        $getParams,
        $container
    ): array {
        $client = null;
        $helpService = new SearchHelpService();
        $queryService = new QueryService();
        $hanlerExSearchService = new ExtSearchService();
        $saleschannel = $saleschannelContext->getSalesChannel();
        $languageId = $saleschannel->getLanguageId();
        $salechannelID = $saleschannel->getId();
        $index = $helpService->findLast($connection, $salechannelID, $languageId, $config);
        $from = $getParams['from'];
        $size = $getParams['size'];
        $params = [
            'index' => $index['index'],
            'from' => $from,
            'size' => $size
        ];
        if (array_key_exists('host', $config)) {
            if ($config['elasticsearchAktive'] == '1') {
                $heandlerClient = new ClientService();
                $client = $heandlerClient->createClient($config);
            }
        }

        if (array_key_exists('filterToSearch', $config)) {
            if ($config['filterToSearch'] === 'yes') {
                $fieldsService = $container->get('s_plugin_sisi_search_es_fields.repository');
                $contextService = new ContextService();
                $context = $contextService->getContext();
                $criteria = new Criteria();
                $criteria->addFilter(new EqualsFilter('fieldtype', 'text'));
                $fieldsconfig = $fieldsService->search($criteria, $context);
                $saleschannelName = $saleschannel->getName();
                $match = $queryService->getTheKindOfMatch($config);
                $fields = [];
                if (sizeOf($fieldsconfig) > 0) {
                    $indexProducts = 0;
                    foreach ($fieldsconfig as $row) {
                        $tablename = $row->getTablename();
                        $str = $hanlerExSearchService->strQueryFields($tablename, $config);
                        $exclude = $row->getExcludesearch();
                        if ($exclude === 'yes') {
                            $str = false;
                        }
                        if ($str) {
                            $shop = $row->getShop();
                            $strChannel = false;
                            if (trim(($saleschannelName)) === trim($shop)) {
                                $strChannel = true;
                            }
                            $name = $helpService->setField($row);
                            $queryService->mergeFields($indexProducts, $fields, $match, $term, $row, $name, $getParams);
                        }
                    }
                }
                $params = $queryService->getQuery($index, $fields, $config, $from, $size);
                $heandlerExtra = new SearchExtraQueriesService();
                $heandlerExtra->addSuggest($params, $config, $term);
                return $this->mergeQueryInRelationToAllfields(
                    $manufactoryIds,
                    $properties,
                    $params,
                    $client,
                    $config,
                );
            }
        }
        $this->mergeQueryInrealtionToOneField($config, $term, $properties, $params);
        $this->mergeManufactory($manufactoryIds, $params);
        return $client->search($params);
    }

    /**
     * @param array $manufactoryIds
     * @param array|null $properties
     * @param array $params
     * @param Client $client
     * @param array $systemConfig
     *
     * @return array
     */
    private function mergeQueryInRelationToAllfields($manufactoryIds, $properties, &$params, $client, $systemConfig)
    {
        $newParam['body']['query']['bool']['must'][0] = $params['body']['query'];
        $this->mergeNestedProperties($properties, $systemConfig, $newParam);
        $manufactory = [];
        if ($manufactoryIds !== null) {
            $manufactoryIds = $this->mergeManufactoryIds($manufactoryIds);
            if (!empty($manufactoryIds)) {
                $manufactory['bool']['must'][] = [
                    'match' => [
                        "manufacturer_id" => [
                            'query' => trim($manufactoryIds)
                        ]
                    ]
                ];
            }
        }
        if (count($manufactory) > 0) {
            $newParam['body']['query']['bool']['must'][] = $manufactory;
        }
        $newParam['index'] = $params['index'];
        $newParam['from'] = $params['from'];
        $newParam['size'] = $params['size'];
        return $client->search($newParam);
    }

    /**
     * @param array $config
     * @param string $term
     * @param array|null $properties
     * @param array $params
     *
     * @return void
     */
    private function mergeQueryInrealtionToOneField($config, $term, $properties, &$params)
    {
        $relationFieled = 'product_name';

        if (array_key_exists('resultpage', $config)) {
            if (!empty($config['resultpage'])) {
                $relationFieled = trim($config['resultpage']);
            }
        }
        if (array_key_exists('filterFuzzy', $config)) {
            if ($config['filterFuzzy'] == 'yes') {
                $params['body']['query']["bool"]["must"][] = [
                    'match' => [
                        $relationFieled => [
                            'query' => trim($term),
                            'fuzziness' => 2
                        ]
                    ]
                ];
            } else {
                $params['body']['query']["bool"]["must"][] = ['match' => [$relationFieled => ['query' => trim($term)]]];
            }
        } else {
            $params['body']['query']["bool"]["must"][] = ['match' => [$relationFieled => ['query' => trim($term)]]];
        }

        $this->mergeNestedProperties($properties, $config, $params);
    }

    /**
     * @param array|null$properties
     * @param array $systemConfig
     * @param array $newParam
     * @return void
     */
    private function mergeNestedProperties($properties, $systemConfig, &$newParam): void
    {
        $propertiesQuery = [];
        if (is_array($properties)) {
            foreach ($properties as $pro) {
                if (array_key_exists('properties', $systemConfig)) {
                    $propertiesArray["path"] = "properties";
                    $propertiesArray["query"]["bool"]['should'][0]['match']['properties.option_id'] = trim($pro);
                    $propertiesArray["query"]["bool"]['should'][1]['match']['properties.option_name'] = trim($pro);
                    $propertiesQuery["nested"] = $propertiesArray;
                }
                $newParam['body']['query']['bool']['must'][] = $propertiesQuery;
            }
        }
    }

    /**
     * @param array|string|null $manufactoryIds
     * @param array $params
     * @return void
     */
    private function mergeManufactory($manufactoryIds, &$params)
    {
        $manufactoryIds = $this->mergeManufactoryIds($manufactoryIds);
        if (!empty($manufactoryIds)) {
            $params['body']['query']["bool"]["must"][] = [
                'match' => [
                    "manufacturer_id" => [
                        'query' => trim($manufactoryIds)
                    ]
                ]
            ];
        }
    }

    /**
     * @param array|string|null $manufactoryIds
     * @return string
     */
    private function mergeManufactoryIds($manufactoryIds)
    {
        if (is_array($manufactoryIds)) {
            $manu = '';
            $index = 0;
            foreach ($manufactoryIds as $id) {
                if ($index == 0) {
                    $manu .= $id;
                } else {
                    $manu .= " " . $id;
                }
                $index++;
            }
            return $manu;
        }
        return '';
    }
}
