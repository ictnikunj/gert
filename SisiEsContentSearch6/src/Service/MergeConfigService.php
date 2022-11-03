<?php

namespace Sisi\SisiEsContentSearch6\Service;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Sisi\Search\Service\ContextService;
use Sisi\Search\Service\TextService;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Core\Content\Cms\CmsPageEntity;
use Elasticsearch\Client;
use Sisi\SisiEsContentSearch6\Service\StemmingService;
use Sisi\Search\Service\ProductExtendService;
use Sisi\SisiEsContentSearch6\Core\Fields\Bundle\ContentFieldsEntity;

class MergeConfigService
{

    public function mergeConfig(array $parameters, ContainerInterface $container): ?ContentFieldsEntity
    {
        $languageName = $this->getLanuageName($container, $parameters["language_id"]);
        $result = $this->getPluginConfig($container, $parameters, $languageName);
        return $result;
    }

    /**
     * @param ContainerInterface $container
     * @param array $parameters
     * @param string $languageName
     * @return mixed|null
     */
    private function getPluginConfig(ContainerInterface $container, array $parameters, string $languageName)
    {
        $pluginconfigHaendler = $container->get('sisi_escontent_fields.repository');
        $contextheandler = new ContextService();
        $criteria = new Criteria();
        $shopName = str_replace("shop=", "", $parameters['shop']);
        $pos = strpos($shopName, "shopID=");
        if ($pos !== false) {
            $channelId = str_replace("shopID=", "", $shopName);
            $shopName = $this->getChannelName($container, $channelId);
        }
        $criteria->addFilter(new EqualsFilter('shop', $shopName));
        $criteria->addFilter(
            new MultiFilter(
                MultiFilter::CONNECTION_OR,
                [
                    new EqualsFilter('language', trim($languageName)),
                    new EqualsFilter('language', ""),
                    new EqualsFilter('language', null)

                ]
            )
        );
        $context = $contextheandler->getContext();
        $pluginconfig = $pluginconfigHaendler->search($criteria, $context)->getEntities()->getElements();
        return array_shift($pluginconfig);
    }

    private function getLanuageName(ContainerInterface $container, string $lanuageId): string
    {
        $contextheandler = new ContextService();
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $lanuageId));
        $context = $contextheandler->getContext();
        $saleschannel = $container->get('language.repository');
        $languageObject = $saleschannel->search($criteria, $context)->getElements();
        $languageObject = array_shift($languageObject);
        return $languageObject->getName();
    }
    private function getChannelName(ContainerInterface $container, string $channelId): string
    {
        $contextheandler = new ContextService();
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $channelId));
        $context = $contextheandler->getContext();
        $saleschannel = $container->get('sales_channel.repository');
        $languageObject = $saleschannel->search($criteria, $context)->getElements();
        $languageObject = array_shift($languageObject);
        return $languageObject->getName();
    }
}
