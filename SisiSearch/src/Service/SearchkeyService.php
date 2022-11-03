<?php

namespace Sisi\Search\Service;

use _HumbugBox2acd634d137b\Symfony\Component\Console\Output\Output;
use Doctrine\DBAL\Connection;
use Elasticsearch\Client;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Content\Product\Aggregate\ProductSearchKeyword\ProductSearchKeywordEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Console\Output\OutputInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Content\Product\Aggregate\ProductSearchKeyword\ProductSearchKeywordCollection;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;

class SearchkeyService
{

    /**
     * @param array $fields
     * @param mixed $entitie
     * @param string $lanuageId
     * @param EntitySearchResult $mappingValues
     * @param array $config
     * @param Logger $loggingService
     * @param string|null $parentid
     * @param InsertService $self
     *
     * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity )
     *
     * @return void
     */
    public function insertSearchkey(
        &$fields,
        $entitie,
        $lanuageId,
        &$mappingValues,
        $config,
        $loggingService,
        $parentid,
        $self,
        $connection
    ) {
        $searchkeywoerter = $entitie->getSearchKeywords();
        if ($searchkeywoerter !== null) {
            $searchkeywoerter = $searchkeywoerter->getElements();
            $searchkeywoert = array_shift($searchkeywoerter);
            $translation = [];
            foreach ($searchkeywoerter as $woert) {
                if (strtoupper($woert->getLanguageId()) === strtoupper($lanuageId)) {
                    $translation[] = $woert->getKeyword();
                }
            }
            if (array_key_exists('addProductNumber', $config)) {
                if ($config['addProductNumber'] === 'yes') {
                    $heandlerids = new ExtendInsertService();
                    if ($entitie->getParentId() == null) {
                        $idsArray = $heandlerids->dbqueryfromStockFromAllVaraints($connection, $entitie->getId());
                        if (count($idsArray) > 0) {
                            foreach ($idsArray as $item) {
                                if (!empty($item['product_number'])) {
                                    $translation[] = $item['product_number'];
                                    $newSearchkeyword = new ProductSearchKeywordEntity();
                                    $newSearchkeyword->setKeyword($item['product_number']);
                                    $newSearchkeyword->setProductId($item['HEX(id)']);
                                    $searchkeywoerter[] = $newSearchkeyword;
                                }
                                if (!empty($item['manufacturer_number'])) {
                                    $translation[] = $item['manufacturer_number'];
                                    $newSearchkeyword = new ProductSearchKeywordEntity();
                                    $newSearchkeyword->setKeyword($item['manufacturer_number']);
                                    $newSearchkeyword->setProductId($item['HEX(id)']);
                                    $searchkeywoerter[] = $newSearchkeyword;
                                }
                            }
                        }
                    }
                }
            }
            $self->checkFunction(
                $mappingValues,
                $fields,
                $searchkeywoert,
                $translation,
                $loggingService,
                $config,
                'search',
                true,
                $parentid,
                ['multivalue' => $searchkeywoerter]
            );
        }
    }

    /**
     * @param SalesChannelRepositoryInterface $entitie
     * @param string $lanuageId
     * @return string
     */
    public function mergeSearchKeyWort($entitie, string $lanuageId): string
    {
        /** @phpstan-ignore-next-line */
        $searchkeywoerter = $entitie->getSearchKeywords();
        $return = '';
        if ($searchkeywoerter !== null) {
            foreach ($searchkeywoerter as $woert) {
                if (strtoupper($woert->getLanguageId()) === strtoupper($lanuageId) && !empty($woert->getKeyword())) {
                    $return .= "," . $woert->getKeyword();
                }
            }
            if (!empty($return)) {
                /** @phpstan-ignore-next-line */
                $translations = $entitie->getTranslations()->getElements();
                foreach ($translations as $translation) {
                    if (strtoupper($translation->getLanguageId()) === strtoupper($lanuageId)) {
                        $return = $translation->getName() . ',' . $return;
                    }
                }
            }
        }
        return $return;
    }

    public function addFilter(array &$return, array $config, array $synoms): void
    {
        if (array_key_exists('searchkeywort', $config) && (count($synoms) > 0)) {
            if ($config['searchkeywort'] === '1') {
                $return["analysis"]["filter"]['product_shopwarekeywords']["type"] = "synonym";
                $return["analysis"]["filter"]['product_shopwarekeywords']["synonyms"] = $synoms;
            }
            if (array_key_exists('analyzer_product_name', $return["analysis"]["analyzer"])) {
                $return["analysis"]["analyzer"]["analyzer_product_name"]['filter'][] = "product_shopwarekeywords";
            }
        }
    }

    /**
     * @param array $config
     * @param SalesChannelRepository | null $productService
     * @param SalesChannelContext $saleschannelContext
     * @param array $paramters
     * @param OutputInterface|null $output
     * @return array
     */
    public function mergeSearchkeywoerter(
        $config,
        $productService,
        $saleschannelContext,
        $paramters,
        $output
    ): array {
        $return = [];
        if (array_key_exists('searchkeywort', $config) && $paramters['counter'] === 0) {
            $texthaendler = new TextService();
            if ($config['searchkeywort'] === '1') {
                $texthaendler->write($output, "First we will merge the Shopware keywords.\n");
                $criteria = new Criteria();
                $criteria->addAssociation('searchKeywords');
                $criteria->addAssociation('translations');
                if (array_key_exists('limit', $paramters)) {
                    $criteria->setlimit($paramters['limit']);
                }
                $lanuageId = $paramters['language_id'];
                $offset = 0;
                $this->getSynomeBysteps(
                    $criteria,
                    $productService,
                    $saleschannelContext,
                    $lanuageId,
                    $return,
                    $paramters,
                    $offset
                );
            }
        }
        return $return;
    }

    private function extendMergekeywords(
        ProductSearchKeywordCollection $searchkeywoerter,
        string $lanuageId,
        int &$index,
        array &$return
    ): void {
        foreach ($searchkeywoerter as $woert) {
            if (
                strtoupper($woert->getLanguageId()) === strtoupper($lanuageId)
                && !empty($woert->getKeyword())
            ) {
                $return[$index] .= "," . $woert->getKeyword();
            }
        }
    }

    /**
     * @param Criteria $criteria
     * @param SalesChannelRepositoryInterface $productService
     * @param SalesChannelContext $saleschannelContext
     * @param string $lanuageId
     * @param array $return
     * @param array $paramters
     * @param int $offset
     *
     */
    private function getSynomeBysteps(
        Criteria &$criteria,
        SalesChannelRepositoryInterface $productService,
        SalesChannelContext $saleschannelContext,
        string $lanuageId,
        array &$return,
        array $paramters,
        int $offset
    ): void {
        $criteria->setOffset($offset);
        $str = true;
        $index = 0;
        while ($str) {
            $result = $this->iterateSynoms(
                $saleschannelContext,
                $criteria,
                $productService,
                $lanuageId,
                $index,
                $return
            );
            if (array_key_exists('limit', $paramters)) {
                $offset = $offset + $paramters['limit'];
            } else {
                $offset = $offset + 1000;
            }
            $criteria->setOffset($offset);
            if (count($result) === 0) {
                $str = false;
            }
        }
    }

    private function iterateSynoms(
        SalesChannelContext $saleschannelContext,
        Criteria &$criteria,
        SalesChannelRepositoryInterface $productService,
        string $lanuageId,
        string &$index,
        array &$return
    ): EntitySearchResult {
        $entities = $productService->search($criteria, $saleschannelContext);
        if ($entities !== false) {
            foreach ($entities as $entity) {
                $searchkeywoerter = $entity->getSearchKeywords();
                if ($searchkeywoerter !== null) {
                    $translation = $entity->getTranslations();
                    foreach ($translation->getElements() as $translationItem) {
                        if ($translationItem->getlanguageId() === $lanuageId) {
                            $return[$index] = '';
                            $this->extendMergekeywords($searchkeywoerter, $lanuageId, $index, $return);
                            if (!empty($return[$index])) {
                                $return[$index] = $translationItem->getName() . '' . $return[$index];
                            }
                        }
                    }
                    $index++;
                }
            }
        }
        return $entities;
    }
}
