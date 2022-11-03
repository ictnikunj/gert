<?php

namespace Sisi\Search\Service;

use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerEntity;
use Shopware\Core\Content\Category\Aggregate\CategoryTranslation\CategoryTranslationEntity;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Console\Output\OutputInterface;
use Shopware\Core\Content\Category\CategoryCollection;

/**
 *   @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ExtendInsertService
{
    public function addSuggesterField(array $config, array &$fields): void
    {
        if (array_key_exists('suggest', $config) && array_key_exists('product_name', $fields)) {
            if ($config['suggest'] === '1') {
                $fields['product_name_trigram'] = $fields['product_name'];
            }
        }
    }

    public function displayInsertRow(array $parameters, OutputInterface $output): void
    {
        if (array_key_exists('showinsert', $parameters)) {
            if ($parameters['showinsert'] === '1') {
                $output->writeln('insert Row');
            }
        }
    }

    /**
     * @param SalesChannelProductEntity $entitie
     * @param array $parameters
     * @param OutputInterface | null $output
     * @param Logger
     *
     *  @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @return bool
     */
    public function checkStockFromAllVaraints(
        $entitie,
        $parameters,
        $output,
        $loggingService
    ) {
        if (array_key_exists('config', $parameters)) {
            if (array_key_exists('onlymain', $parameters['config']) && array_key_exists('connection', $parameters)) {
                if ($parameters['config']['onlymain'] === 'stock') {
                    $allVaraints = $this->dbqueryfromStockFromAllVaraints($parameters['connection'], $entitie->getId());
                    $stock = 0;
                    $strWithOutMain = true;
                    foreach ($allVaraints as $varaint) {
                        $stock = $stock + $varaint['stock'];
                    }
                    if (array_key_exists('stockwithoutmain', $parameters['config'])) {
                        if ($parameters['config']['stockwithoutmain'] == 'yes') {
                            $strWithOutMain = false;
                        }
                    }
                    if ($strWithOutMain) {
                        $stock = $stock + $entitie->getStock();
                    }
                    $messasge = 'Product with the number ' . $entitie->getProductNumber() . ' are  not indexed because the stock is empty';
                    if ($stock == 0) {
                        if ($output !== null) {
                            $output->writeln($messasge);
                        }
                        $loggingService->log('100', $messasge);
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * @param array $config
     * @param array $merker
     * @param SalesChannelProductEntity $entitie
     * @return bool
     */
    public function checkRemoveDouble($config, &$merker, $entitie)
    {
        if (array_key_exists('onlymain', $config)) {
            if ($config['onlymain'] === 'nodouple') {
                $name = $entitie->getName();
                $name = str_replace(' ', '', $name);
                if (in_array($name, $merker)) {
                    return false;
                } else {
                    $merker[] = $name;
                }
            }
        }
        return true;
    }

    public function dbqueryfromStockFromAllVaraints(connection $connection, string $id): array
    {
        $query = $connection->createQueryBuilder()
            ->select(['HEX(id),stock,product_number,manufacturer_number, HEX(parent_id)'])
            ->from('product')
            ->where('parent_id = UNHEX(:id)')
            ->setParameter(':id', $id);
        return $query->execute()->fetchAll();
    }


    /**
     * @param array $parameters
     * @param ProgressService $progress
     * @param OutputInterface | null $output
     */
    public function echoLastLine($parameters, $progress, $output): void
    {
        if (!array_key_exists('backend', $parameters)) {
            $progress->showProgressBar(100, 2, $output);
        }
    }

    /**
     * @param \Sisi\Search\Service\InsertService $self
     * @param ProductManufacturerEntity|null $manufacturers
     * @param array $config
     * @param \Sisi\Search\Service\TranslationService $haendlerTranslation
     * @param EntitySearchResult $mappingValues
     * @param array $fields
     * @param Logger $loggingService
     * @param string $lanugageId
     * @param string|null $parentId
     *
     * @return void
     */
    public function setManufacturerValue(
        InsertService $self,
        $manufacturers,
        $config,
        $haendlerTranslation,
        $mappingValues,
        &$fields,
        $loggingService,
        $lanugageId,
        $parentId
    ) {
        if (method_exists($manufacturers, 'getTranslations')) {
            $translation = $haendlerTranslation->getTranslationfields($manufacturers->getTranslations(), $lanugageId);
            $self->checkFunction(
                $mappingValues,
                $fields,
                $manufacturers,
                $translation,
                $loggingService,
                $config,
                $table = 'manufacturer',
                $isArrayFrom = true
            );
            if ($translation) {
                $custoMmanufacturer = $translation->getCustomFields();
                $self->setCustomsFileds(
                    $custoMmanufacturer,
                    $mappingValues,
                    $fields,
                    $loggingService,
                    $config,
                    $parentId
                );
            } else {
                $custoMmanufacturer = $manufacturers->getCustomFields();
                $self->setCustomsFileds(
                    $custoMmanufacturer,
                    $mappingValues,
                    $fields,
                    $loggingService,
                    $config,
                    $parentId
                );
            }
        } else {
            if (method_exists($manufacturers, 'getCustomFields')) {
                $custoMmanufacturer = $manufacturers->getCustomFields();
                $self->setCustomsFileds(
                    $custoMmanufacturer,
                    $mappingValues,
                    $fields,
                    $loggingService,
                    $config,
                    $parentId
                );
            }
            $self->checkFunction(
                $mappingValues,
                $fields,
                $manufacturers,
                $manufacturers,
                $loggingService,
                $config,
                $table = 'manufacturer',
                $isArrayFrom = true,
                $parentId
            );
        }
    }
    /**
     *   @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function insertCategorie(
        TranslationService $haendlerTranslation,
        array $params,
        EntitySearchResult $mappingValues,
        array &$fields,
        Logger $loggingService,
        InsertService $self,
        array &$categorieMerker
    ): void {
        $categorieFields = $haendlerTranslation->getTranslationfields(
            $params['categorie']->getTranslations(),
            $params['lanugageId']
        );
        $categorieId = trim($params['categorie']->getId());
        $strCategory = in_array($categorieId, $params['categoriesValue']);
        $merkerCatId = [];
        if (!in_array($categorieId, $categorieMerker) && $strCategory) {
            $index = 0;
            $help = [];

            foreach ($params['categories']->getElements() as $categorie) {
                if ($categorie->getType() === 'page') {
                    $categorieTranslation = $haendlerTranslation->getTranslationfields(
                        $categorie->getTranslations(),
                        $params['lanugageId']
                    );
                    $categorieid = trim($categorie->getId());
                    if (!in_array($categorieid, $merkerCatId)) {
                        $help[$index]['category_id'] = $categorieid;
                        $merkerCatId[] = $categorieid;
                        foreach ($mappingValues as $mappingValue) {
                            if ($mappingValue->getTablename() === 'category') {
                                $methode = "get" . ucfirst($mappingValue->getName());
                                $prefix = strtolower($mappingValue->getPrefix());
                                $indexName = 'category_' . $prefix . strtolower($mappingValue->getName());
                                $this->insertCatgorieToArray(
                                    $categorieTranslation,
                                    $index,
                                    $categorie,
                                    $methode,
                                    $indexName,
                                    $help
                                );
                                $help[$index]['category_breadcrumb'] = $this->mergebreadcrumb($categorieTranslation, $categorie, $params['config']);
                            }
                        }
                    }
                }
                $index++;
            }
            $fields['categories'] = $help;
            $fields['category_id'] = $categorieId;
            $self->checkFunction(
                $mappingValues,
                $fields,
                $params['categorie'],
                $categorieFields,
                $loggingService,
                $params['config'],
                'category',
                true,
                $params['parentid'],
                ['multivalue' => $params['categories']]
            );
            if ($categorieFields) {
                $self->setCustomsFileds(
                    $categorieFields->getCustomFields(),
                    $mappingValues,
                    $fields,
                    $loggingService,
                    $params['config'],
                    $params['parentid']
                );
            }
            $categorieMerker[] = $categorieId;
        }
    }

    /**
     * @param CategoryEntity|bool $categorieTranslation
     * @param array $config
     * @param int $index
     * @param CategoryEntity $categorie
     * @param string $name
     * @param string $indexName
     * @param array $help
     * @return void
     */
    private function insertCatgorieToArray(
        $categorieTranslation,
        $index,
        $categorie,
        $name,
        string $indexName,
        &$help
    ) {
        if ($categorieTranslation !== false) {
            $value = trim($categorieTranslation->$name());
        } else {
            $value = trim($categorie->$name());
        }
        if (!empty($value)) {
            $help[$index][$indexName] = $value;
        }
    }

    /**
     * @param CategoryTranslationEntity $categorieTranslation
     * @param CategoryEntity|bool $categorie
     * @param array $config
     * @return string
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function mergebreadcrumb($categorieTranslation, $categorie, $config)
    {
        $depth = 0;
        $seperator = " ";
        $values = null;

        if (array_key_exists('breadcrumbDepth', $config)) {
            $depth = $config['breadcrumbDepth'];
        }

        if ($categorieTranslation == null or $categorieTranslation == false) {
            return '';
        }

        if (array_key_exists('breadcrumbseparator', $config)) {
            $seperator = $config['breadcrumbseparator'];
        }
        if ($categorieTranslation !== false) {
            $values = $categorieTranslation->getBreadcrumb();
        }

        $value = "";
        $orginal = $categorie->getPlainBreadcrumb();
        if ($values !== null) {
            $values = $orginal;
            $values = $this->sortbreadcrumb($orginal, $values);
            $index = 0;
            foreach ($values as $item) {
                if (!empty($item) && ($index >= $depth)) {
                    $value .= $seperator . $item;
                }
                $index++;
            }
        }

        return trim($value);
    }

    private function sortbreadcrumb(array $orgianl, array $translated): array
    {
        $return = [];
        foreach ($orgianl as $key => $item) {
            $return[$key] = $translated[$key];
        }
        return $return;
    }

    public function insertProperties(
        TranslationService $haendlerTranslation,
        array $params,
        array &$fields,
        EntitySearchResult $mappingValues,
        Logger $loggingService,
        InsertService $self,
        array &$propertiesMerker
    ): void {
        $propertyId = trim($params['property']->getId());
        foreach ($params['property']->getOptions() as $option) {
            $optionTranslation = $haendlerTranslation->getTranslationfields(
                $option->getTranslations(),
                $params['lanugageId']
            );
            $name = $option->getName();
            if ($optionTranslation !== false) {
                $name = $optionTranslation->getName();
            }
            $fields['properties'][] = [
                'property_id' => $propertyId,
                'property_group' => $params['property']->getName(),
                'option_name' => $name,
                'option_id' => $option->getId()
            ];
            $self->checkFunction(
                $mappingValues,
                $fields,
                $params['property'],
                $optionTranslation,
                $loggingService,
                $params['config'],
                'properties',
                true,
                $params['parentid']
            );
            $propertiesMerker[] = $propertyId;
        }
    }

    /**
     * @param mixed $translation
     * @param string $name
     * @param mixed $entitie
     * @param array $ext
     * @return string
     */
    public function getTranslation($translation, $name, $entitie, $ext)
    {
        $value = '';
        if (!array_key_exists("multivalue", $ext)) {
            if (is_string($translation) || is_object($translation)) {
                if (method_exists($translation, $name)) {
                    $value = $translation->$name();
                }
            }
            $value = $this->getValueFromDefaultLanguage($name, $entitie, $value);
            if (empty($value)) {
                $value = $entitie->$name();
            }
        } else {
            $index = 0;
            foreach ($ext['multivalue'] as $entry) {
                if ($index === 0) {
                    $value = $entry->$name();
                } else {
                    $value .= " " . $entry->$name();
                }
                $index++;
            }
        }
        return $value;
    }

    /**
     * @param string $name
     * @param mixed $entitie
     * @param string $value
     * @return string
     */
    private function getValueFromDefaultLanguage($name, $entitie, $value)
    {
        if (empty($value)) {
            if ($entitie->getTranslations() && method_exists($entitie->getTranslations(), 'getElements')) {
                $elements = $entitie->getTranslations()->getElements();
                foreach ($elements as $transElement) {
                    if ($transElement->getLanguageId() === Defaults::LANGUAGE_SYSTEM) {
                        if (method_exists($transElement, $name)) {
                            return $transElement->$name();
                        }
                    }
                }
            }
        }
        return $value;
    }
}
