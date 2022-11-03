<?php

namespace Sisi\SisiEsContentSearch6\Service;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Sisi\Search\Service\ContextService;
use Sisi\Search\Service\ProgressService;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Core\Content\Cms\CmsPageEntity;
use Elasticsearch\Client;
use Sisi\SisiEsContentSearch6\Service\StemmingService;
use Sisi\Search\Service\ProductExtendService;

class IndexStartService
{
    /**
     * @param OutputInterface|null $output
     * @param Client $client
     * @param array $parameters
     * @param ContainerInterface $container
     * @param SystemConfigService $configHaendler
     * @return void
     */
    public function startIndex($output, $client, $parameters, $container, $configHaendler)
    {
        $handlerContent = new InsertContentService();
        $heandlerMergeConfig = new MergeConfigService();
        $outputheanlder = new OutPutService();
        $config = $configHaendler->get("SisiEsContentSearch6.config");
        $backendConfig = $heandlerMergeConfig->mergeConfig($parameters, $container);
        $content = $handlerContent->mergeContent($container, $parameters["language_id"], $parameters["shop"], $config);
        $body['index'] = "content_" . $parameters['esIndex'];
        $parameters['esIndex'] =  $body['index'];
        $body['body']['settings'] = [];
        $body['body']['mappings'] = [];
        if ($config == null) {
            $outputheanlder->write($output, "You need a configuration for SisiEsContentSearch6 \n");
        } else {
            $handlerSettings = new MergeSettingsService();
            $handlerSettings->getSettings($body['body']['settings'], $backendConfig);
            $handlerMapping = new MergeMappingService();
            $handlerMapping->getMapping($body['body']['mappings']);
            $handlerContent->createIndex($client, $body);
            $handlerContent->insertContentToES($content, $parameters, $client, $output, $backendConfig);
            $outputheanlder->write($output, 'Content indexierung are now finish');
        }
    }
}
