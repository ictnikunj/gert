<?php

namespace Sisi\SisiEsContentSearch6\Service;

use ONGR\ElasticsearchDSL\Search;
use Sisi\Search\Service\ProductMoreService;
use Sisi\Search\Service\ProductService;

class StemmingService
{
    /**
     * @param string $stemming
     * @param array $settings
     * @return void
     */
    public function mergeStemmingFilter(string $stemming, array &$settings): void
    {
        $settings["analysis"]["filter"]["CMS_Stemmer"] = [
            "type" => "stemmer",
            "language" => $stemming
        ];
    }

    /**
     * @param string $stemming
     * @param array $settings
     * @return void
     */
    public function mergeStemmingStop(string $stemming, array &$settings)
    {
        $handler = new ProductMoreService();
        $stemming = (string) $handler->checkstopWort($stemming);
        $settings["analysis"]["filter"]["CMS_Stemming_stop"] = [
            "type" => "stop",
            "language" => $stemming
        ];
    }
}
