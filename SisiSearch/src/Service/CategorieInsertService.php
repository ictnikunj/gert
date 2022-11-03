<?php

namespace Sisi\Search\Service;

use Shopware\Core\Content\Category\CategoryEntity;

class CategorieInsertService
{

    public function mergeFields(array $fieldConfig, &$fields, array $config, CategoryEntity $entitie, array $parameter): void
    {
        $headlerInsert = new InsertService();
        $heandlerExtendInsert = new ExtendInsertService();
        $heandlerExtendSearch = new ExtSearchService();
        $haendlerTranslation = new TranslationService();
        foreach ($fieldConfig as $configItem) {
            $name =  $configItem->getPrefix() . $configItem->getTablename() . "_" . $configItem->getName();
            $translation = $entitie->getTranslations();
            $fieldsTranslation = $haendlerTranslation->getTranslationfields(
                $translation,
                $parameter['language_id']
            );
            $ext["mutivalue"] = $fieldsTranslation;
            $functionName = "get" . lcfirst($configItem->getName());
            $value = $heandlerExtendInsert->getTranslation($fieldsTranslation, $functionName, $entitie, $ext);
            $value = $headlerInsert->removeSpecialCharacters($value, $configItem);
            $value = $heandlerExtendSearch->stripUrl($value, $config);
            $value =  $headlerInsert->stripContent($value, $configItem);
            $fields[$name] = $value;
        }
        $fields['category_id'] = $entitie->getId();
        $fields['category_breadcrumb'] = $heandlerExtendInsert->mergebreadcrumb($fieldsTranslation, $entitie, $config);
    }
}
