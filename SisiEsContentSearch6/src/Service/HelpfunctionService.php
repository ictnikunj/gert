<?php

namespace Sisi\SisiEsContentSearch6\Service;

class HelpfunctionService
{
    public function removeDoubleCategories(array $configContent, array &$contentResult): void
    {
        if (array_key_exists("doppelcategorie", $configContent)) {
            if ($configContent['doppelcategorie'] === '1') {
                $newHits = [];
                $merker = [];
                $hits = $contentResult['hits']['hits'];
                foreach ($hits as $hit) {
                    $categorieId = $hit['_source']['categorie_ids'];
                    if (!in_array($categorieId, $merker)) {
                        $newHits[] = $hit;
                        $merker[] = $categorieId;
                    }
                }
                $contentResult['hits']['hits'] = $newHits;
            }
        }
    }
}
