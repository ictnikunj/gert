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

class OutPutService
{
    public function write(?OutputInterface $output, string $text): void
    {
        if ($output === null) {
            echo $text;
        } else {
            $output->write($text);
        }
    }
}
