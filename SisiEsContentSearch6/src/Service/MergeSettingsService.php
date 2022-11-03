<?php

namespace Sisi\SisiEsContentSearch6\Service;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Sisi\Search\Service\ContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Core\Content\Cms\CmsPageEntity;
use Elasticsearch\Client;
use Sisi\SisiEsContentSearch6\Service\StemmingService;
use Sisi\Search\Service\ProductExtendService;
use Sisi\SisiEsContentSearch6\Core\Fields\Bundle\ContentFieldsEntity;

class MergeSettingsService
{

    /**
     * @param array $settings
     * @param ContentFieldsEntity|null$backendconfig
     * @return void
     *
     * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
     * @SuppressWarnings(PHPMD)
     */
    public function getSettings(array &$settings, ?ContentFieldsEntity $backendconfig)
    {
        $minGram = 3;
        $maxGram  = 3;
        $filter = [];
        $tokenizer = "ngram";
        $stopsWords = "";

        if (!empty($backendconfig->getStop())) {
            $valuestring = str_replace("\n", "", $backendconfig->getStop());
            $stopsWords = explode(",", $valuestring);
        }

        if (!empty($backendconfig->getMinedge())) {
            $minGram = (int)$backendconfig->getMinedge();
        }

        if (!empty($backendconfig->getEdge())) {
            $maxGram  = (int)$backendconfig->getEdge();
        }

        if (!empty($backendconfig->getFilter1()) && $backendconfig->getFilter1() !== 'noselect') {
            $filter[] = $backendconfig->getFilter1();
        }

        if (!empty($backendconfig->getFilter2()) && $backendconfig->getFilter2() !== 'noselect') {
            $filter[] = $backendconfig->getFilter2();
        }

        if (!empty($backendconfig->getFilter3()) && $backendconfig->getFilter3() !== 'noselect') {
            $filter[] = $backendconfig->getFilter3();
        }


        if (!empty($backendconfig->getTokenizer())) {
            $tokenizer = $backendconfig->getTokenizer();
        }


        if (!empty($backendconfig->getStemming()) && $backendconfig->getStemming() !== 'noselect') {
            $stemminghandler = new StemmingService();
            $stemminghandler->mergeStemmingFilter($backendconfig->getStemming(), $settings);
            $filter[] = "CMS_Stemmer";
        }
        if (!empty($backendconfig->getStemmingstop())) {
            $stemminghandler = new StemmingService();
            if ($backendconfig->getStemmingstop() === 'yes' && $backendconfig->getStemming() !== 'no') {
                $stemminghandler->mergeStemmingStop($backendconfig->getStemming(), $settings);
                $filter[] = "CMS_Stemming_stop";
            }
        }

        $settings["analysis"]["tokenizer"]["CMS_tokenizer"] = [
            "type" => $tokenizer,
            "token_chars" => ["letter", "digit"]
        ];
        $settings["analysis"]["analyzer"]["analyzer_CMS"] = [
            "tokenizer" => $tokenizer,
        ];

        if ($tokenizer === "ngram" || $tokenizer === "edge_ngram") {
            $tokenizer = 'CMS_tokenizer';
            $settings["analysis"]["tokenizer"]["CMS_tokenizer"]["min_gram"] = $minGram;
            $settings["analysis"]["tokenizer"]["CMS_tokenizer"]["max_gram"] = $maxGram ;
            $settings["analysis"]["analyzer"]["analyzer_CMS"] = [
                "type" => "custom"
            ];

            $settings["analysis"]["analyzer"]["analyzer_CMS"] = [
                "tokenizer" => 'CMS_tokenizer',
            ];
        }

        $handler = new ProductExtendService();
        if (is_array($stopsWords)) {
            $stopsWords = $handler->removeEmptyElemnetsFromArray($stopsWords);
        }
        if (!empty($stopsWords)) {
            $settings["analysis"]["filter"]["custom_stop"] = [
                "type" => "stop",
                "stopwords" => $stopsWords,
            ];
            $filter[] = "custom_stop";
        }

        $settings["analysis"]["filter"]["autocomplete"] = [
            "type" => "edge_ngram",
             "edge_ngram" => $minGram,
             "max_gram" => $maxGram
        ];


        if (count($filter) > 0) {
            $settings["analysis"]["analyzer"]["analyzer_CMS"]["filter"] = $filter;
        }
    }
}
