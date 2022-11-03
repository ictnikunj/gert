<?php

namespace Sisi\Search\Service;

use Doctrine\DBAL\Connection;
use Elasticsearch\Client;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Sisi\Search\Service\ContextService;
use Sisi\Search\ServicesInterfaces\InterfaceSearchCategorieService;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

/**
 * @SuppressWarnings(PHPMD)
 */
class CategorieIndexService
{
    /**
     * @param ContainerInterface $container
     * @param array $parameter
     * @param Connection $connection
     * @param SystemConfigService $config
     * @param OutputInterface | null $output
     * @param Logger $loggingService
     * @param InterfaceSearchCategorieService $searchCategorieService
     * @return void|false
     */
    public function startIndex($container, $parameter, $connection, $config, $output, $loggingService, $searchCategorieService)
    {
        if (array_key_exists('update', $parameter)) {
            if ($parameter['update'] === '4') {
                return false;
            }
        }
        $contextService = new ContextService();
        $context = $contextService->getContext();
        $criteriaHandler = new CriteriaService();
        $categorieHandler = new CategorieService();
        $timeHaendler = new InsertTimestampService();
        $heandlerClient = new ClientService();
        $heandlerMappings = new CategorieIndexMappingService();
        $transHaendler = new TranslationService();
        $texthaendler = new TextService();
        $indexHaendler = new IndexService();
        $texthaendler->write($output, "Index from the Categories is now started");
        $parameter['update'] = "1";
        $saleschannel = $container->get('sales_channel.repository');
        $criteriaChannel = new Criteria();#;
        $criteria = $searchCategorieService->createCriteria();
        if (array_key_exists('shop', $parameter)) {
            $shop = $parameter['shop'];
            // string manipulation extract channel
            $shop = str_replace("shop=", "", $shop);
        }
        if (array_key_exists('shopID', $parameter)) {
            $shop = "shopID=" . $parameter['shopID'];
        }
        $criteriaHandler->getMergeCriteriaForSalesChannel($criteriaChannel, $shop);
        $salechannelItem = $saleschannel->search($criteriaChannel, $context)->getEntities()->first();
        $channelId = $salechannelItem->getId();
        $config =  $config->get("SisiSearch.config", $channelId);
        $categorieId = $salechannelItem->getNavigationCategoryId();
        $footerId = $salechannelItem->getFooterCategoryId();
        $categorieTree = $categorieHandler->getAllCategories($container, $categorieId);
        $haendler = $container->get('category.repository');
        $categorien = $haendler->search($criteria, $context)->getEntities()->getElements();
        $lanugageValues = $transHaendler->getLanguageId($parameter, $connection, $output, $loggingService);
        $lanugageId = $transHaendler->chechIsSetLanuageId($lanugageValues, $salechannelItem, $parameter);
        $parameter['language_id'] = $lanugageId;
        $lanuageName = $indexHaendler->getLanuageNameById($connection, $lanugageId);
        $parameter['language'] = $lanuageName;
        if ($footerId != null) {
            $categorieFooterTree = $categorieHandler->getAllCategories($container, $footerId);
            $categorieTree = array_merge($categorieTree, $categorieFooterTree);
        }
        $serviceId = $salechannelItem->getServiceCategoryId();
        if ($serviceId != null) {
            $categorieFooterTree = $categorieHandler->getAllCategories($container, $serviceId);
            $categorieTree = array_merge($categorieTree, $categorieFooterTree);
        }
        $client = $heandlerClient->createClient($config);
        $fieldConfig = $this->getFieldConfig($container, $context);
        $params['index'] = "categorien_" . $this->createIndexname($lanugageId, $channelId);
        $params['body']['settings'] = $searchCategorieService->createCategorySettings($fieldConfig, $config);
        $heandlerMappings->delteIndex($client, $params['index'], $loggingService);
        $params['body']['mappings'] = $searchCategorieService->createCategoryMapping($fieldConfig);
        $excludecategories = $this->explodeCategorie($config, "categorynoinindex");
        $excludecategoriesByids = $this->explodeCategorie($config, "categorynoinindexbyId");
        $excludecategoriesByids = array_map('strtolower', $excludecategoriesByids);
        $result = $heandlerMappings->createMappingCategory($client, $params);
        foreach ($categorien as $categoykey => $category) {
            $name = $category->getName();
            $id = strtolower($category->getId());
            if (in_array($categoykey, $categorieTree) && !in_array($name, $excludecategories) && !in_array($id, $excludecategoriesByids)) {
                $insertResult = $searchCategorieService->insertValue($client, $params['index'], $category, $fieldConfig, $config, $parameter);
            }
        }
    }

    public function getFieldConfig(ContainerInterface $container, Context $context)
    {
        $fieldsService = $container->get('s_plugin_sisi_search_es_fields.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('tablename', "category"));
        return $fieldsService->search($criteria, $context)->getEntities()->getElements();
    }

    public function createIndexname(string $language, string $channel): string
    {
        return strtolower($channel) . "_" . strtolower($language);
    }

    private function explodeCategorie(array $config, string $arrayindex): array
    {
        $return = [];
        if (array_key_exists($arrayindex, $config)) {
            $return = explode("\n", $config[$arrayindex]);
        }
        return  $return;
    }
}
