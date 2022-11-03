<?php declare(strict_types=1);

namespace PimImport\Controller;

use Exception;
use Shopware\Core\Content\Media\Exception\DuplicatedMediaFileNameException;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\File\MediaFile;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 */
class CategoryManageOrderController extends AbstractController
{
    /**
     * @var SystemConfigService
     */
    private $systemConfigService;
    private $categoryRepository;
    private $languageRepository;
    private $mediaRepository;
    private $mediaService;
    private $fileSaver;
    private $cmsPageRepository;
    private $productRepository;
    private $salesChannelRepository;
    private $productCategoryPositionRepository;
    private $kplngi_orderactive;
    private $pimCategoryRepository;

    const domain = "https://fluidmaster.compano.com";

    public function __construct(
        SystemConfigService $systemConfigService,
        EntityRepositoryInterface $categoryRepository,
        EntityRepositoryInterface $languageRepository,
        EntityRepositoryInterface $mediaRepository,
        MediaService $mediaService,
        FileSaver $fileSaver,
        EntityRepositoryInterface $cmsPageRepository,
        EntityRepositoryInterface $productRepository,
        EntityRepositoryInterface $salesChannelRepository,
        EntityRepositoryInterface $productCategoryPositionRepository,
        EntityRepositoryInterface $kplngi_orderactive,
        EntityRepositoryInterface $pimCategoryRepository
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->categoryRepository = $categoryRepository;
        $this->languageRepository = $languageRepository;
        $this->mediaRepository = $mediaRepository;
        $this->mediaService = $mediaService;
        $this->fileSaver = $fileSaver;
        $this->cmsPageRepository = $cmsPageRepository;
        $this->productRepository = $productRepository;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->productCategoryPositionRepository = $productCategoryPositionRepository;
        $this->kplngi_orderactive = $kplngi_orderactive;
        $this->pimCategoryRepository = $pimCategoryRepository;
    }

    /**
     * @Route("/api/pim/categoryorderimport", name="api.action.pim.category.order.import", methods={"GET"})
     */
    public function categoryImport(Context $context, $cron = null): JsonResponse
    {
        $categoryURL = $this->systemConfigService->get('PimImport.config.pimCategoryUrl');
        $apiParameters = '?';
        $apiKey = $this->systemConfigService->get('PimImport.config.pimApiKey');
        $CategoryPublicationCode = $this->systemConfigService->get('PimImport.config.CategoryPublicationCode');
        $cat_Array = $this->systemConfigService->get('PimImport.config.SequenceManage');
        if (!$cat_Array) {
            $cat_Array = array();
        }
        if ($cron == 1) {
            $dbSalesChannelId = 1;
        } else {
            $dbSalesChannelId = $_GET['salesChannelId'] ?? false;
        }
        $counter = 1;
        $last_counter = 1;
        $count99 = 1;

        //get all sales channel
        if ($dbSalesChannelId) {
            if ($cron == 1) {
                $getAllSalesChannels = $this->getAllSalesChannel($context);
            } else {
                $getAllSalesChannels = $this->getSalesChannelUsingId($dbSalesChannelId, $context);
            }

            foreach ($getAllSalesChannels as $getAllSalesChannel) {
                $customFields = $getAllSalesChannel->getcustomFields();

                if (isset($customFields['custom_pim_sales_channel_publication_code'])) {
                    $PublicationCode = $customFields['custom_pim_sales_channel_publication_code'];
                    //store current sales channel
                    $this->systemConfigService->set('PimImport.config.CategoryPublicationCode', $PublicationCode);
                    if ($cron == 1) {
                        echo $PublicationCode;
                    }
                    //get two main hierarchy
                    $mainCategoryID = $getAllSalesChannel->getnavigationCategoryId();
                    $subCategories = $this->getChildren($mainCategoryID, $context);
                    if ($subCategories) {
                        foreach ($subCategories as $subCategoryID => $subCategory) {
                            //it should be added as new node after "PRODUCTS" in the category structure
                            if (trim($subCategory->getName()) == 'Product' || trim($subCategory->getName()) == 'Products' || trim($subCategory->getName()) == 'Producten' || trim($subCategory->getName()) == 'Izdelki' || trim($subCategory->getName()) == 'Termékek' || trim($subCategory->getName()) == 'Produkte' || trim($subCategory->getName()) == 'Productos' || trim($subCategory->getName()) == 'Proizvodi' || trim($subCategory->getName()) == 'Produse' || trim($subCategory->getName()) == 'Produkty' || trim($subCategory->getName()) == 'Prodotti') {
                                if ($subCategory->getafterCategoryId() != null) {
                                    $mainData = [
                                        'id' => $subCategory->getId(),
                                        'afterCategoryId' => null
                                    ];
                                    $this->categoryRepository->upsert([$mainData], $context);
                                }

                                $getTotalCategory = $this->getpimAPIdata($categoryURL . $apiParameters . $apiKey . '&filter=PublicationCode=' . $PublicationCode);
                                if (isset($getTotalCategory->Count)) {
                                    $last_counter = $getTotalCategory->Count;

                                    if ($cron == 1) {
                                        //for cron
                                        $perPage = $last_counter;
                                        $counter = 1;
                                    } else {
                                        if ($_GET['counter']) {
                                            //for ajax
                                            $perPage = 1;
                                            $counter = $_GET['counter'];
                                        } else {
                                            $perPage = 10;//$last_counter
                                            $counter = 1;
                                        }
                                    }

                                    $apiUrl = $categoryURL . '/' . $counter . '/' . $perPage . $apiParameters . $apiKey . '&filter=PublicationCode=' . $PublicationCode;

                                    $categoryAPIData = $this->getpimAPIdata($apiUrl);

                                    if ($categoryAPIData->PublicationNodes) {
                                        $l=1;
                                        foreach ($categoryAPIData->PublicationNodes as $res) {
                                            $Origin = $res->Origin->Value;
                                            $Node = $res->Code->Value;
                                            if ($Node == 99) {
                                                $cat_Array = array();
                                            }
                                            if (isset($res->ProductGroupDetails->Level->Value)) {
                                                $SequenceNo = $res->ProductGroupDetails->Level->Value;
                                            } else {
                                                $SequenceNo = $count99;
                                            }
                                            if ($Origin == 'Generated' && $res->ProductGroupDetails) {
                                                if ($Node == 99) {
                                                    $subCategoryID = $mainCategoryID;
                                                }
                                                $categoryCode = $res->Code->Value;
                                                $categoryName = $res->ProductGroupDetails->Description->Value;
                                                $checkCategoryExist = $this->checkCategoryExist(
                                                    $categoryName,
                                                    $subCategoryID,
                                                    $categoryCode,
                                                    $context
                                                );

                                                if ($checkCategoryExist) {
                                                    $categoryData = array();
                                                    $categoryData['id'] = $checkCategoryExist;
                                                    $categoryData['parentId'] = $subCategoryID;
                                                    $CommercialDescription = $res->ProductGroupDetails->CommercialDescription->Value;
                                                    $categoryID = $this->categoryInsert(
                                                        $SequenceNo,
                                                        $cat_Array,
                                                        $categoryData,
                                                        $context
                                                    );
                                                    /*if($Node != 99) {*/
                                                    $cat_Array[$SequenceNo][] = $categoryID;
                                                    /*}*/
                                                    $ChildNodes = $res->ChildNodes;

                                                    if ($ChildNodes) {
                                                        $count99++;
                                                        $ChildNodes = $this->arrayCustomMultiSort($ChildNodes);
                                                        $this->insertProductGroupRecursive(
                                                            $count99,
                                                            $cat_Array,
                                                            $categoryID,
                                                            $ChildNodes,
                                                            $context
                                                        );
                                                    }
                                                }
                                                $checkUpdatedProduct = $this->checkPimCategory($checkCategoryExist);
                                                if (!empty($checkUpdatedProduct) || $checkUpdatedProduct != null) {
                                                    $submainData = [
                                                            'id' => $checkUpdatedProduct->getcategoryId(),
                                                            'afterCategoryId' => $subCategory->getId()
                                                        ];
                                                    $this->categoryRepository->upsert([$submainData], $context);
                                                }
                                            }

                                            if ($Origin == 'Manual' && $res->Description) {
                                                if ($Node == 99) {
                                                    $subCategoryID = $mainCategoryID;
                                                }
                                                $MCategoryName = $res->Description->Value;
                                                $categoryCode = $res->Code->Value;
                                                $MCheckCategoryExist = $this->checkCategoryExist(
                                                    $MCategoryName,
                                                    $subCategoryID,
                                                    $categoryCode,
                                                    $context
                                                );

                                                if ($MCheckCategoryExist) {
                                                    $MCategoryData = array();
                                                    $MCategoryData['id'] = $MCheckCategoryExist;
                                                    $MCategoryData['parentId'] = $subCategoryID;
                                                    $MCategoryID = $this->categoryInsert(
                                                        $SequenceNo,
                                                        $cat_Array,
                                                        $MCategoryData,
                                                        $context
                                                    );
                                                    /*if($Node != 99) {*/
                                                    $cat_Array[$SequenceNo][] = $MCategoryID;
                                                    /*}*/
                                                    $MChildNodes = $res->ChildNodes;
                                                    if ($MChildNodes) {
                                                        $count99++;
                                                        $MChildNodes = $this->arrayCustomMultiSort($MChildNodes);
                                                        $this->insertProductGroupRecursive(
                                                            $count99,
                                                            $cat_Array,
                                                            $MCategoryID,
                                                            $MChildNodes,
                                                            $context
                                                        );
                                                    }
                                                }

                                                $checkUpdatedProduct = $this->checkPimCategory($MCheckCategoryExist);
                                                if (!empty($checkUpdatedProduct) || $checkUpdatedProduct != null) {
                                                    $submainData = [
                                                            'id' => $checkUpdatedProduct->getcategoryId(),
                                                            'afterCategoryId' => $subCategory->getId()
                                                        ];
                                                    $this->categoryRepository->upsert([$submainData], $context);
                                                }
                                            }

                                            $counter++;
                                            $this->systemConfigService->set('PimImport.config.CategoryCounter', $counter);
                                            $this->systemConfigService->set('PimImport.config.SequenceManage', $cat_Array);
                                            $l++;
                                        }
                                    }
                                }
                            }
                        }
                        if ($counter == $last_counter + 1) {
                            //reset counter
                            $this->systemConfigService->set('PimImport.config.CategoryCounter', 1);
                            //reset sequence
                            $this->systemConfigService->set('PimImport.config.SequenceManage', '');
                        }
                        if ($cron != 1) {
                            return new JsonResponse([
                                'type' => 'success',
                                'message' => 'Success',
                                'counter' => $counter,
                                'endcounter' => $last_counter + 1,
                                'currentPublicationCode' => $PublicationCode,
                                'DatabasePublicationCode' => $CategoryPublicationCode
                            ]);
                        }
                    } else {
                        if ($cron != 1) {
                            return new JsonResponse([
                                'type' => 'error',
                                'message' => 'Sub Category Product Word not Found Or Please select entry point',
                            ]);
                        }
                    }
                } else {
                    if ($cron != 1) {
                        return new JsonResponse([
                            'type' => 'error',
                            'message' => 'Custom Field Empty',
                        ]);
                    }
                }
            }
            if ($cron == 1) {
                return new JsonResponse([
                    'type' => 'success',
                    'message' => 'Success',
                ]);
            }
        } else {
            if ($cron == 1) {
                return new JsonResponse([
                    'type' => 'success',
                    'message' => 'Success',
                ]);
            } else {
                return new JsonResponse([
                    'type' => 'error',
                    'message' => 'Please select sales channel',
                ]);
            }
        }
    }

    public function arrayCustomMultiSort($ChildNodes)
    {
        $tempArray = array();
        foreach ($ChildNodes as $childNode) {
            $tempArray[$childNode->SequenceNo->Value] = $childNode;
        }
        ksort($tempArray);
        return $tempArray;
    }

    public function insertProductGroupRecursive($count99, $cat_Array, $subCategoryID, $ChildNodes, $context)
    {
        foreach ($ChildNodes as $res) {
            //echo "<pre>";print_r($cat_Array);echo "</pre>";
            $Origin = $res->Origin->Value;

            if (isset($res->ProductGroupDetails->Level->Value)) {
                $SequenceNo = $res->ProductGroupDetails->Level->Value;
            } else {
                $SequenceNo = $count99;
            }

            if ($Origin == 'Generated' && $res->ProductGroupDetails) {
                $categoryName = $res->ProductGroupDetails->Description->Value;
                $categoryCode = $res->Code->Value;
                $checkCategoryExist = $this->checkCategoryExist($categoryName, $subCategoryID, $categoryCode, $context);
                if ($checkCategoryExist) {
                    $categoryData = array();
                    $categoryData['id'] = $checkCategoryExist;
                    $categoryData['parentId'] = $subCategoryID;
                    $CommercialDescription = $res->ProductGroupDetails->CommercialDescription->Value;
                    $categoryData['translations'] = $this->setCategoryTranslation($categoryName, $CommercialDescription, $context);
                    $categoryID = $this->categoryInsert($SequenceNo, $cat_Array, $categoryData, $context);
                    $cat_Array[$SequenceNo][] = $categoryID;
                    $ChildNodes = $res->ChildNodes;

                    if ($ChildNodes) {
                        $count99++;
                        $ChildNodes = $this->arrayCustomMultiSort($ChildNodes);
                        $this->insertProductGroupRecursive($count99, $cat_Array, $categoryID, $ChildNodes, $context);
                        $count99--;
                    }
                }
            }

            if ($Origin == 'Manual' && $res->Description) {
                $MCategoryName = $res->Description->Value;
                $categoryCode = $res->Code->Value;
                $MCheckCategoryExist = $this->checkCategoryExist($MCategoryName, $subCategoryID, $categoryCode, $context);
                if ($MCheckCategoryExist) {
                    $MCategoryData = array();
                    $MCategoryData['id'] = $MCheckCategoryExist;
                    $MCategoryData['parentId'] = $subCategoryID;
                    $MCommercialDescription = $res->FullDescription->Value;
                    $MCategoryData['translations'] = $this->setCategoryTranslation($MCategoryName, $MCommercialDescription, $context);
                    $MCategoryID = $this->categoryInsert($SequenceNo, $cat_Array, $MCategoryData, $context);
                    $cat_Array[$SequenceNo][] = $MCategoryID;
                    $MChildNodes = $res->ChildNodes;
                    if ($MChildNodes) {
                        $count99++;
                        $MChildNodes = $this->arrayCustomMultiSort($MChildNodes);
                        $this->insertProductGroupRecursive($count99, $cat_Array, $MCategoryID, $MChildNodes, $context);
                        $count99--;
                    }
                }
            }
        }
    }

    //get api data
    public function getpimAPIdata($api_url)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return json_decode($response);
        }
    }

    // Insert Category
    public function categoryInsert($SequenceNo, $cat_Array, array $categoryData, Context $context): string
    {
        $categoryID = $categoryData['id'];

        $data = [
            'id'            => $categoryData['id'],
            'parentId'      => $categoryData['parentId']
        ];
        //afterCategoryId
        if (isset($cat_Array) && isset($cat_Array[$SequenceNo])) {
            if (end($cat_Array[$SequenceNo])) {
                $data['afterCategoryId'] = end($cat_Array[$SequenceNo]);
            }
        } else {
            $data['afterCategoryId'] = null;
        }

        $this->categoryRepository->upsert([$data], $context);

        //add position in kpi
//        if(isset($categoryData['kplngiPositions']) && $categoryID){
//            foreach($categoryData['kplngiPositions'] as $product){
//                $this->setkplngiPosition($product['productId'],$categoryID,$product['position'],$context);
//            }
//        }

        return $categoryData['id'];
    }

    //update category id in product
    public function setkplngiPosition($productId, $categoryId, $position, $context)
    {
        $checkKPCategoryExist = $this->checkKPCategoryExist($categoryId, $context);
        if (!$checkKPCategoryExist) {
            $this->kplngi_orderactive->upsert(
                [
                    [
                        'id' => Uuid::randomHex(),
                        'categoryId' => $categoryId,
                    ]
                ],
                $context
            );
        }

        $this->productCategoryPositionRepository->upsert(
            [
                [
                    'productId' => $productId,
                    'categoryId' => $categoryId,
                    'position'=>$position
                ]
            ],
            $context
        );
    }

    //check category
    public function checkKPCategoryExist(string $categoryId, Context $context): ?string
    {
        //set up the criteria for the search
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('categoryId', $categoryId));
        return $this->kplngi_orderactive->searchIds($criteria, $context)->firstId();
    }

    //get all sales channel
    public function getAllSalesChannel($context): array
    {
        $criteria = new Criteria();
        return $this->salesChannelRepository->search($criteria, $context)->getEntities()->getElements();
    }

    //get sales channel using id
    public function getSalesChannelUsingId($dbSalesChannelId, $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $dbSalesChannelId));

        return $this->salesChannelRepository->search($criteria, $context)->getEntities()->getElements();
    }

    //get category id
    public function getCategoryId(string $categoryName, Context $context)
    {
        //set up the criteria for the search
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $categoryName));

        //search the category repository, based on the criteria
        $categories = $this->categoryRepository->search($criteria, $context);
        if ($categories) {
            $category = $categories->first();
            return $category->getId();
        } else {
            return '';
        }
    }

    //get child id
    public function getSubCategoryId(string $parentId, string $categoryName, Context $context)
    {
        //set up the criteria for the search
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $categoryName));
        $criteria->addFilter(new EqualsFilter('parentId', $parentId));

        //search the category repository, based on the criteria
        $categories = $this->categoryRepository->search($criteria, $context);

        if ($categories) {
            $category = $categories->first();
            if ($category) {
                return $category->getId();
            } else {
                return '';
            }
        } else {
            return '';
        }
    }

    //get all child
    public function getChildren(string $parentId, Context $context): array
    {
        //set up the criteria for the search
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('parentId', $parentId));

        //search the category repository, based on the criteria
        return $this->categoryRepository->search($criteria, $context)->getEntities()->getElements();
    }


    //check category
    public function checkCategoryExist(Object $categoryName, string $parentId, $categoryCode, Context $context): ?string
    {
        //set up the criteria for the search
        $languageKey = $this->getDefaultLanguageKey($context);
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('parentId', $parentId));
        $criteria->addFilter(new EqualsFilter('customFields.custom_pim_category_code', $categoryCode));
        $id = $this->categoryRepository->searchIds($criteria, $context)->firstId();
        if ($id != null) {
            return $id;
        }
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $categoryName->$languageKey));
        $criteria->addFilter(new EqualsFilter('parentId', $parentId));
        return $this->categoryRepository->searchIds($criteria, $context)->firstId();
    }

    public function getDefaultLanguageKey(Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $context->getlanguageIdChain()[0]));
        $criteria->addAssociation('locale');
        $data = $this->languageRepository->search($criteria, $context)->first();
        $languageKey = null;
        if ($data->getName() == 'English') {
            $languageKey = 'en';
        }
        if ($data->getName() == 'Dutch' || $data->getName() == 'Nederlands') {
            $languageKey = 'nl';
        }
        if ($data->getName() == 'Deutsch') {
            $languageKey = 'de';
        }
        if ($data->getName() == 'Magyar') {
            $languageKey = 'hu';
        }
        if ($data->getName() == 'Italiano') {
            $languageKey = 'it';
        }
        if ($data->getName() == 'Croatian') {
            $languageKey = 'hr';
        }
        if ($data->getName() == 'Polski') {
            $languageKey = 'pl';
        }
        if ($data->getName() == 'Română') {
            $languageKey = 'ro';
        }
        if ($data->getName() == 'Slovenian') {
            $languageKey = 'sl';
        }
        if ($data->getName() == 'Español') {
            $languageKey = 'es';
        }
        if ($data->getName() == 'Čeština') {
            $languageKey = 'cs';
        }
        return $languageKey;
    }

    // Check the Language
    public function setCategoryTranslation(object $categoryName, object $CommercialDescription, Context $context): array
    {
        $languageData = array();
        $criteria = new Criteria();
        $datas = $this->languageRepository->search($criteria, $context)->getElements();

        foreach ($datas as $data) {
            if ($data->getName() == 'English') {
                $languageKey = 'en';
                $languageData[$data->getId()]['name'] = $categoryName->$languageKey;
            }
            if ($data->getName() == 'Dutch' || $data->getName() == 'Nederlands') {
                $languageKey = 'nl';
                $languageData[$data->getId()]['name'] = $categoryName->$languageKey;
            }
            if ($data->getName() == 'Deutsch') {
                $languageKey = 'de';
                $languageData[$data->getId()]['name'] = $categoryName->$languageKey;
            }
            if ($data->getName() == 'Magyar') {
                $languageKey = 'hu';
                $languageData[$data->getId()]['name'] = $categoryName->$languageKey;
            }
            if ($data->getName() == 'Italiano') {
                $languageKey = 'it';
                $languageData[$data->getId()]['name'] = $categoryName->$languageKey;
            }
            if ($data->getName() == 'Croatian') {
                $languageKey = 'hr';
                $languageData[$data->getId()]['name'] = $categoryName->$languageKey;
            }
            if ($data->getName() == 'Polski') {
                $languageKey = 'pl';
                $languageData[$data->getId()]['name'] = $categoryName->$languageKey;
            }
            if ($data->getName() == 'Română') {
                $languageKey = 'ro';
                $languageData[$data->getId()]['name'] = $categoryName->$languageKey;
            }
            if ($data->getName() == 'Slovenian') {
                $languageKey = 'sl';
                $languageData[$data->getId()]['name'] = $categoryName->$languageKey;
            }
            if ($data->getName() == 'Español') {
                $languageKey = 'es';
                $languageData[$data->getId()]['name'] = $categoryName->$languageKey;
            }
            if ($data->getName() == 'Čeština') {
                $languageKey = 'cs';
                $languageData[$data->getId()]['name'] = $categoryName->$languageKey;
            }
        }
        return $languageData;
    }

    // Check the Product
    public function getProductID($productNumber = null)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productNumber', $productNumber));
        $productArray = $this->productRepository->searchIds($criteria, Context::createDefaultContext())->getIds();
        return $productArray[0] ?? '';
    }

    public function checkPimCategory($checkCategoryExist)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('categoryId', $checkCategoryExist));
        $criteria->addFilter(new EqualsFilter('categoryCode', "99"));
        $criteria->addFilter(new EqualsFilter('salesChannelId', $_GET['salesChannelId']));
        return $this->pimCategoryRepository->search($criteria, Context::createDefaultContext())->first();
    }
}
