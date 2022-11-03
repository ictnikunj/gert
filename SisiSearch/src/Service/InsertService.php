<?php

namespace Sisi\Search\Service;

use Doctrine\DBAL\Exception;
use Elasticsearch\Client;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx\Properties;
use Shopware\Core\Content\Media\Pathname\UrlGeneratorInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Sisi\Search\Core\Content\Fields\Bundle\DBFieldsEntity;
use Sisi\SisiEsContentSearch6\ESindexing\InsertQueryDecorator;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class InsertService
 * @package Sisi\Search\Service
 * @SuppressWarnings(PHPMD)
 */
class InsertService
{
    /**
     * @param EntitySearchResult $entities
     * @param EntitySearchResult $mappingValues
     * @param Client $client
     * @param string $lanugageId
     * @param Logger $loggingService
     * @param OutputInterface | null $output
     * @param Mixed $insertQuery
     * @param array $parameters
     * @param ContainerInterface $container
     *
     */
    public function setIndex(
        &$entities,
        $mappingValues,
        $client,
        $lanugageId,
        $loggingService,
        $output,
        $insertQuery,
        $parameters,
        $container
    ): void {
        $total = $entities->getTotal();
        $progress = new ProgressService();
        $haendlerTime = new InsertTimestampService();
        $haendlerTranslation = new TranslationService();
        $heandlerExtendInsert = new ExtendInsertService();
        $heandlercategorie = new CategorieService();
        $heandlerSynms = new SearchkeyService();
        $texthaendler = new TextService();
        $heandlerChannelData = new ChannelDataService();
        $heandlerIndex = new IndexService();
        $heandlerPropterties = new PropertiesService();
        $categoriesValue = $heandlercategorie->getAllCategories($container, $parameters['categorie_id']);
        $counter = 0;
        $categorieMerker = [];
        $propertiesMerker = [];
        $mem_usage = memory_get_usage();
        $merker = [];
        $texthaendler->write($output, round($mem_usage / 1048576, 2) . " megabytes \n");
        foreach ($entities as $entitie) {
            if ($heandlerExtendInsert->checkStockFromAllVaraints($entitie, $parameters, $output, $loggingService) && $heandlerExtendInsert->checkRemoveDouble($parameters['config'], $merker, $entitie)) {
                $fields = [];
                $percentage = $counter / $total * 100;
                $parentId = trim($entitie->getParentId());
                if (!array_key_exists('backend', $parameters)) {
                    $progress->showProgressBar($percentage, 2, $output);
                }
                $translation = $haendlerTranslation->getTranslationfields($entitie->getTranslations(), $lanugageId);
                $this->checkFunction(
                    $mappingValues,
                    $fields,
                    $entitie,
                    $translation,
                    $loggingService,
                    $parameters['config'],
                    'product',
                    false,
                    $parentId
                );

                if ($translation) {
                    $this->setCustomsFileds(
                        $translation->getCustomFields(),
                        $mappingValues,
                        $fields,
                        $loggingService,
                        $parameters['config'],
                        $parentId
                    );
                }
                $heandlerSynms->insertSearchkey(
                    $fields,
                    $entitie,
                    $lanugageId,
                    $mappingValues,
                    $parameters['config'],
                    $loggingService,
                    $parentId,
                    $this,
                    $parameters['connection']
                );
                if ($heandlercategorie->strIndexCategorie($parameters['config'])) {
                    $categoieStream = $heandlercategorie->getProductStreamsCategories($entitie);
                    $categories = $entitie->getCategories();
                    $heandlercategorie->getMergeCategories($categories, $categoieStream);
                    foreach ($categories as $categorie) {
                        $params['categorie'] = $categorie;
                        $params['lanugageId'] = $lanugageId;
                        $params['categoriesValue'] = $categoriesValue;
                        $params['parentid'] = $parentId;
                        $params['config'] = $parameters['config'];
                        $params['categories'] = $categories;
                        $heandlerExtendInsert->insertCategorie(
                            $haendlerTranslation,
                            $params,
                            $mappingValues,
                            $fields,
                            $loggingService,
                            $this,
                            $categorieMerker
                        );
                    }
                }
                $manufacturers = $entitie->getManufacturer();
                if ($heandlerIndex->checkManufacturer($manufacturers, $fields)) {
                    $heandlerExtendInsert->setManufacturerValue(
                        $this,
                        $manufacturers,
                        $parameters['config'],
                        $haendlerTranslation,
                        $mappingValues,
                        $fields,
                        $loggingService,
                        $lanugageId,
                        $parentId
                    );
                }
                $fields['id'] = $entitie->getId();
                $fields['language'] = $lanugageId;
                $fields['channel'] = $heandlerChannelData->getDatas($entitie, $parameters['config'], $lanugageId, $parameters['urlGenerator']);
                $fields['properties'] = [];
                $heandlerPropterties->setSortedProperties($fields, $entitie, $parameters);
                if ($entitie->getSortedProperties()) {
                    foreach ($entitie->getSortedProperties() as $property) {
                        $paramsPro['property'] = $property;
                        $paramsPro['lanugageId'] = $lanugageId;
                        $paramsPro['parentid'] = $parentId;
                        $paramsPro['config'] = $parameters['config'];
                        $heandlerExtendInsert->insertProperties(
                            $haendlerTranslation,
                            $paramsPro,
                            $fields,
                            $mappingValues,
                            $loggingService,
                            $this,
                            $propertiesMerker
                        );
                    }
                }
                $heandlerExtendInsert->addSuggesterField($parameters['config'], $fields);
                $haendlerTime->deleteEntry($parameters, $client, $entitie);
                $insertresult = $insertQuery->insertValue($entitie, $client, $parameters['esIndex'], $fields);
                if ($insertresult["_shards"]["failed"]) {
                    $loggingService->log('100', 'Insert fail id ' . $insertresult["_id"]);
                }
                $counter++;
            }
        }
        $heandlerExtendInsert->echoLastLine($parameters, $progress, $output);
    }

    /**
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings("PMD.CyclomaticComplexity")
     * @param EntitySearchResult $mappingValues
     * @param array $fields
     * @param mixed $entitie
     * @param mixed $translation
     * @param Logger $loggingService
     * @param array $config
     * @param string $table
     * @param bool $isArrayFrom
     * @param string|null $parentId
     *
     */

    public function checkFunction(
        EntitySearchResult &$mappingValues,
        array &$fields,
        $entitie,
        $translation,
        Logger $loggingService,
        array $config,
        string $table = 'product',
        bool $isArrayFrom = false,
        $parentId = null,
        array $ext = []
    ): void {
        $productnameValue = '';
        $countName = 0;
        foreach ($mappingValues as $mappingValue) {
            $name = 'get' . ucfirst($mappingValue->getName());
            $tablename = $mappingValue->getTablename();
            $exlude = $mappingValue->getExclude();
            $heandlerExtendInsert = new ExtendInsertService();
            $heandlerExtendSearch = new ExtSearchService();
            $strInsert = false;
            $isonlymain = $mappingValue->getOnlymain();
            $strValueInProductname = [];
            if ($entitie !== null && !empty($entitie)) {
                if (method_exists($entitie, $name) && ($exlude != '1' || $parentId == null) || ($name === "properties_name")) {
                    $strInsert = true;
                }
            }
            if (method_exists($mappingValue, 'getActive')) {
                if (!$mappingValue->getActive()) {
                    $strInsert = false;
                }
            }
            if ($strInsert) {
                $value = $heandlerExtendInsert->getTranslation($translation, $name, $entitie, $ext);
                $value = $this->removeSpecialCharacters($value, $mappingValue);
                $value = $heandlerExtendSearch->stripUrl($value, $config);
                $name = $tablename . '_' . $mappingValue->getName();
                $isCorrectType = $this->checkFieldTypeIsCorrect(
                    $value,
                    $mappingValue->getFieldtype(),
                    $loggingService,
                    $name
                );
                if ($isCorrectType && ($tablename == $table)) {
                    $merge = $mappingValue->getMerge();
                    if ($merge === 'yes' && $name !== "product_name") {
                        $productnameValue .= $this->stripContent($value, $mappingValue);
                    } else {
                        $name = $mappingValue->getPrefix() . $name;
                        $value = $this->stripContent($value, $mappingValue);
                        $fields[$name] = $value;
                        $this->mergeField($isonlymain, $name, $value, $fields, $entitie);
                    }
                }
            } elseif ($isArrayFrom != false && $table == $tablename) {
                $loggingService->log('100', 'Data field not available ' . $name);
            }
        }
        if (array_key_exists('product_name', $fields)) {
            if (!empty($productnameValue)) {
                if (array_key_exists('product_namenest', $fields)) {
                    if (is_array($fields['product_namenest'])) {
                        $text = 'The product name is only available for main products and can’t be use for inside mapping';
                        $loggingService->log('100', $text);
                        throw new Exception($text);
                    }
                }
                $fields['product_name'] .= '|' . $productnameValue;
            }
        }
    }

    /**
     * @param string $isonlymain
     * @param string $name
     * @param string $value
     * @param array $fields
     * @param mixed $entitie
     * @return void
     */
    private function mergeField(string $isonlymain, string $name, string $value, array &$fields, $entitie)
    {
        $onlymain = "0";
        if (method_exists($entitie, 'getParentId')) {
            $parentId = $entitie->getParentId();
            if (empty($parentId)) {
                $onlymain = "1";
            }
        }
        if ($isonlymain === 'yes') {
            $fields[$name . "nest"] = [
                $name => $value,
                "onlymain" => $onlymain
            ];
        }
    }

    /**
     * @param string|null $value
     * @param string $fieldType
     * @param Logger $loggingService
     * @param string $name
     * @return bool
     */
    private function checkFieldTypeIsCorrect($value, string $fieldType, Logger $loggingService, string $name): bool
    {
        $type = gettype($value);
        $return = false;
        $fieldTypeValues = [
            'text',
            'keyword',
            'date',
            'integer',
            'float',
            'short',
            'byte'
        ];

        if ($type == null) {
            $loggingService->log('100', 'Data field error value empty');
        }
        if ($type === 'string' && in_array($fieldType, $fieldTypeValues)) {
            $return = true;
        }
        if ($type === 'integer') {
            $return = true;
        }

        if ($type === 'float') {
            $return = true;
        }

        if ($type === 'double') {
            $return = true;
        }

        if ($type === 'long') {
            $return = true;
        }

        if ($type === 'array') {
            $return = true;
        }

        if ($return == false && ($value != null)) {
            $loggingService->log('100', 'Data field error ' . $name);
        }
        return $return;
    }

    /**
     * @param string $content
     * @param DBFieldsEntity $mappingValue
     * @return string|void
     */

    public function stripContent($content, $mappingValue)
    {
        $stripTags = '';
        if ($mappingValue->getStrip_str() !== 'yes') {
            return trim($content);
        }
        if ($mappingValue->getStrip_str() === 'yes' && !empty($mappingValue->getStrip())) {
            $stripTagsValues = explode(",", $mappingValue->getStrip());
            foreach ($stripTagsValues as $stripTagsValue) {
                $stripTags .= "<" . $stripTagsValue . ">";
            }
        }
        if (!empty($stripTags)) {
            return strip_tags(trim($content), $stripTags);
        } elseif ($mappingValue->getStrip_str() === 'yes') {
            return strip_tags(trim($content));
        }
    }

    /**
     * @param string $content
     * @param DBFieldsEntity $mappingValue
     * @return string|void
     */
    public function removeSpecialCharacters($content, $mappingValue)
    {
        if (!empty($mappingValue->getPhpfilter())) {
            $specialCharaters = explode("\n", $mappingValue->getPhpfilter());
            foreach ($specialCharaters as $special) {
                $content = str_replace($special, "", $content);
            }
        }
        return $content;
    }


    /**
     *
     * @SuppressWarnings("PMD.CyclomaticComplexity")
     * @param array|null $customfields
     * @param EntitySearchResult $mappingValues
     * @param array $fields
     * @param Logger $loggingService
     * @param array $config
     * @param string|null $parentId
     */

    public function setCustomsFileds(
        $customfields,
        EntitySearchResult $mappingValues,
        array &$fields,
        Logger $loggingService,
        $config,
        $parentId = null
    ): void {
        if ($customfields != null) {
            $productnameValue = '';
            $heandlerExtendSearch = new ExtSearchService();
            foreach ($customfields as $key => $customfield) {
                foreach ($mappingValues as $mappingValue) {
                    $name = $mappingValue->getName();
                    $exlude = $mappingValue->getExclude();
                    if (trim($key) == trim($name)) {
                        $name = $mappingValue->getTablename() . '_' . $name;
                        $isCorrectType = $this->checkFieldTypeIsCorrect(
                            $customfield,
                            $mappingValue->getFieldtype(),
                            $loggingService,
                            $name
                        );
                        if ($isCorrectType && ($exlude != '1' || $parentId == null)) {
                            $value = $this->stripContent($customfield, $mappingValue);
                            $value = $this->removeSpecialCharacters($value, $mappingValue);
                            $value = $heandlerExtendSearch->stripUrl($value, $config);
                            $merge = $mappingValue->getMerge();
                            if ($merge === 'yes' && $name !== "product_name") {
                                $productnameValue .= $this->stripContent(trim($value), $mappingValue);
                            } else {
                                $name = $mappingValue->getPrefix() . $name;
                                $fields[$name] = $this->stripContent(trim($value), $mappingValue);
                            }
                        }
                    }
                }
            }
            if (!empty($productnameValue)) {
                if (array_key_exists('product_name', $fields)) {
                    $fields['product_name'] .= '<br>' . $productnameValue;
                } else {
                    $fields['product_name'] = $productnameValue;
                }
            }
        }
    }
}
