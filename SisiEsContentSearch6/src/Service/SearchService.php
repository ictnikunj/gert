<?php

namespace Sisi\SisiEsContentSearch6\Service;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Search\SearchPageLoadedEvent;
use Sisi\Search\Events\SisiSearchPageLoadedEvent;
use Sisi\Search\Service\ClientService;
use Sisi\Search\Service\ContextService;
use Sisi\Search\Service\SearchHelpService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Core\Content\Cms\CmsPageEntity;
use Elasticsearch\Client;

/**
 * @SuppressWarnings(PHPMD)
 */
class SearchService
{
    public function searchContent(
        string $term,
        array $index,
        int $size,
        client $client,
        array $config,
        bool $strIsCategory = false,
        $categorieId = null
    ): array {
        $fields[] = "CMS_Content";
        $contentIndex = "content_" . $index['index'];
        $multimatch = [
            'query' => trim($term),
            'fields' => $fields
        ];
        $query = ['multi_match' => $multimatch];
        if ($strIsCategory && !empty($categorieId)) {
            $query["bool"]['must'][0] = $query;
            $query["bool"]['must'][1]["term"]["categorie_ids"] = $categorieId;
            unset($query["multi_match"]);
        }
        $params = [
            'index' => $contentIndex,
            "from" => 0,
            "size" => $size,
            'body' => [
                'query' => $query,
                'highlight' => [
                    'pre_tags' => ["<b>"], // not required
                    'post_tags' => ["</b>"], // not required,
                    'fields' => [
                        'CMS_Content' => new \stdClass()
                    ],
                    'require_field_match' => false
                ]
            ]
        ];
        if (array_key_exists('length', $config)) {
            if (!empty($config['length']) && is_numeric($config['length'])) {
                $params['body']['highlight']['fragment_size'] = $config['length'];
            }
        }
        if (array_key_exists('fuzzy', $config) && !$strIsCategory) {
            if ($config['fuzzy'] !== '0') {
                $params['body']['query']['multi_match']['fuzziness'] = $config['fuzzy'];
            }
        }
        if (array_key_exists('operator', $config) && !$strIsCategory) {
            if ($config['operator'] === 'and') {
                $params['body']['query']['multi_match']['operator'] = $config['operator'];
            }
        }

        if ($strIsCategory) {
            $params['body']['highlight']['fragment_size'] = 1;
        }

        return $client->search($params);
    }

    /**
     *
     * @phpstan-ignore-next-line
     **/
    public function search(
        $event,
        SystemConfigService $systemConfigService,
        Connection $connection,
        string $term = null,
        bool $str = true,
        $categorieId = null
    ): bool {
        $page = $event->getPage();
        $size = 10;
        $saleschannelContext = $event->getSalesChannelContext();
        $saleschannel = $saleschannelContext->getSalesChannel();
        $languageId = $saleschannel->getLanguageId();
        $helpService = new SearchHelpService();
        $heandlerHelpfunction = new HelpfunctionService();
        $salechannelID = $saleschannel->getId();
        $config = $systemConfigService->get("SisiSearch.config", $salechannelID);
        $configContent = $systemConfigService->get("SisiEsContentSearch6.config", $salechannelID);
        if (array_key_exists("hits", $configContent)) {
            $size = $configContent['hits'];
        }
        if (array_key_exists("elasticsearchAktive", $configContent)) {
            if ($configContent['elasticsearchAktive'] === '2') {
                return false;
            }
        }
        if (array_key_exists('host', $config) && !empty($config['host'])) {
            $heandlerClient = new ClientService();
            $client = $heandlerClient->createClient($config);
            $searchHandler = new SearchService();
            if (is_string($term)) {
                $index = $helpService->findLast($connection, $salechannelID, $languageId, $config);
                $contentResult = $searchHandler->searchContent(
                    $term,
                    $index,
                    $size,
                    $client,
                    $configContent,
                    $str,
                    $categorieId
                );
                $heandlerHelpfunction->removeDoubleCategories($configContent, $contentResult);
                $page->assign(['sisi_contentResult' => $contentResult]);
                $page->assign(['sisi_contentConfig' => $configContent]);
            }
        }
        return true;
    }
}
