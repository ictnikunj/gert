<?php

namespace Sisi\SisiEsContentSearch6\Service;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Sisi\Search\Service\ContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Core\Content\Cms\CmsPageEntity;
use Elasticsearch\Client;

class MergeMappingService
{
    public function getMapping(array &$mapping): void
    {
        $mapping["properties"]["CMS_Content"] = [
            "type" => "text",
            "analyzer" => "analyzer_CMS"
        ];
        $mapping["properties"]["CMS_Source"] = [
            "type" => "object",
            "enabled" => false
        ];
        $mapping["properties"]["categorie_ids"] = [
            "type" => "text"
        ];
        $mapping["properties"]["CMS_Titel"] = [
            "type" => "text",
            "analyzer" => "analyzer_CMS"
        ];
    }
}
