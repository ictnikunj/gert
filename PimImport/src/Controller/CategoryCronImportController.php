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
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 */
class CategoryCronImportController extends AbstractController
{
    const DOMAIN = "https://fluidmaster.compano.com";
//    const SALES_CHANNEL_ID = "1fdf31125ccf428b8421e2752d3137c4"; //WISA ES
//    const SALES_CHANNEL_ID = "bc2742597dbc4492936c46e64742aa1a"; //Wavedesign NL
//    public const SALES_CHANNEL_ID = "226061ebc3e346929d4730f1ba2f6e30"; //WISA ES

    private $systemConfigService;
    private $categoryRepository;
    private $languageRepository;
    private $mediaRepository;
    private $mediaService;
    private $fileSaver;
    private $cmsPageRepository;
    private $productRepository;
    private $salesChannelRepository;
    private $pimCategoryRepository;
    private $productCategoryPositionRepository;
    private $kplngi_orderactive;
    private $category_cron_saleschannel;

    public function __construct(
        SystemConfigService       $systemConfigService,
        EntityRepositoryInterface $categoryRepository,
        EntityRepositoryInterface $languageRepository,
        EntityRepositoryInterface $mediaRepository,
        MediaService              $mediaService,
        FileSaver                 $fileSaver,
        EntityRepositoryInterface $cmsPageRepository,
        EntityRepositoryInterface $productRepository,
        EntityRepositoryInterface $salesChannelRepository,
        EntityRepositoryInterface $pimCategoryRepository,
        EntityRepositoryInterface $productCategoryPositionRepository,
        EntityRepositoryInterface $kplngi_orderactive,
        EntityRepositoryInterface $category_cron_saleschannel
    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->categoryRepository = $categoryRepository;
        $this->languageRepository = $languageRepository;
        $this->mediaRepository = $mediaRepository;
        $this->mediaService = $mediaService;
        $this->fileSaver = $fileSaver;
        $this->cmsPageRepository = $cmsPageRepository;
        $this->productRepository = $productRepository;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->pimCategoryRepository = $pimCategoryRepository;
        $this->productCategoryPositionRepository = $productCategoryPositionRepository;
        $this->kplngi_orderactive = $kplngi_orderactive;
        $this->category_cron_saleschannel = $category_cron_saleschannel;
    }

    /**
     * @Route("/api/pim/categorycronimport", name="api.action.pim.category.cron.import", methods={"GET"})
     */
    public function categoryCronImport(Context $context): JsonResponse
    {
        //get config        
        $categoryURL = $this->systemConfigService->get('PimImport.config.pimCategoryUrl');
        $apiKey = $this->systemConfigService->get('PimImport.config.pimApiKey');        
        $cat_Array = $this->systemConfigService->get('PimImport.config.SequenceManage');
        if (!$cat_Array) {
            $cat_Array = [];
        }

        //initialize variable
        $counter = $last_counter = $count99 = 1;
        $cron = 0;
        $apiParameters = '?';
        $allCategoryData = [];
        $date = date('Y-m-d');

        //get all sales channel
        $getAllSalesChannels = $this->getSalesChannelUsingId($context);        
        foreach ($getAllSalesChannels as $getAllSalesChannel) {
            $salesChannelId = $getAllSalesChannel->getId();
            if ($salesChannelId == "53708406071e4e01b59d8fd54a65a0e2" || $salesChannelId == "bc2742597dbc4492936c46e64742aa1a") {
                $customFields = $getAllSalesChannel->getcustomFields();
                if (isset($customFields['custom_pim_sales_channel_publication_code']) && !empty($customFields['custom_pim_sales_channel_publication_code'])) {
                    $PublicationCode = $customFields['custom_pim_sales_channel_publication_code'];
                    $checkUpdatedSalesChannel = $this->checkUpdatedSalesChannel($date, $salesChannelId, $context);
                    if ($checkUpdatedSalesChannel === 0) {
                        //get two main hierarchy
                        $mainCategoryID = $getAllSalesChannel->getnavigationCategoryId();
                        $subCategories = $this->getChildren($mainCategoryID, $context);
                        if ($subCategories) {
                            //Import Category
                            file_put_contents(
                                "CategoryImportLog.txt",
                                date("l jS \of F Y h:i:s A") . "> " . $PublicationCode . " Start Import Category\n",
                                FILE_APPEND
                            );
                            foreach ($subCategories as $subCategoryID => $subCategory) {
                                if (trim($subCategory->getName()) == 'Product' || trim($subCategory->getName()) == 'Products' || trim($subCategory->getName()) == 'Producten' || trim($subCategory->getName()) == 'Izdelki' || trim($subCategory->getName()) == 'Termékek' || trim($subCategory->getName()) == 'Produkte' || trim($subCategory->getName()) == 'Productos' || trim($subCategory->getName()) == 'Proizvodi' || trim($subCategory->getName()) == 'Produse' || trim($subCategory->getName()) == 'Produkty' || trim($subCategory->getName()) == 'Prodotti') {
                                    $getTotalCategory = $this->getpimAPIdata($categoryURL . $apiParameters . $apiKey . '&filter=PublicationCode=' . $PublicationCode);
                                    if (isset($getTotalCategory->Count)) {
                                        $perPage = $getTotalCategory->Count;
                                        $counter = 1;
                                        $apiUrl = $categoryURL . '/' . $counter . '/' . $perPage . $apiParameters . $apiKey . '&filter=PublicationCode=' . $PublicationCode;
                                        $categoryAPIData = $this->getpimAPIdata($apiUrl);
                                        if ($categoryAPIData->PublicationNodes) {
                                            foreach ($categoryAPIData->PublicationNodes as $res) {
                                                $Origin = $res->Origin->Value;
                                                $Node = $res->Code->Value;
                                                $SequenceNo = $res->ProductGroupDetails->Level->Value ?? '0';
                                                if ($Origin == 'Generated' && $res->ProductGroupDetails) {
                                                    if ($Node == 99) {
                                                        $subCategoryID = $mainCategoryID;
                                                    }
                                                    $categoryCode = $res->Code->Value;
                                                    $categoryName = $res->ProductGroupDetails->Description->Value;
                                                    $checkCategoryExist = $this->checkCategoryExist($categoryName, $subCategoryID, $categoryCode, $context);
                                                    $categoryData = [];
                                                    if (isset($checkCategoryExist)) {
                                                        $categoryData['id'] = $checkCategoryExist;
                                                    } else {
                                                        $categoryData['id'] = Uuid::randomHex();
                                                    }
                                                    $categoryData['code'] = $res->Code->Value;
                                                    $categoryData['parentId'] = $subCategoryID;
                                                    $categoryData['lastModificationDateTime'] = $res->ProductGroupDetails->LastModificationDateTime->Value;
                                                    $CommercialDescription = $res->ProductGroupDetails->CommercialDescription->Value;
                                                    $categoryData['translations'] = $this->setCategoryTranslation($categoryName, $CommercialDescription, $context);
                                                    //check image exist or not in shopware if not exist so add
                                                    if ($res->ProductGroupDetails->Image) {
                                                        $catImgUrl = self::DOMAIN . $res->ProductGroupDetails->Image->Value;
                                                        if ($catImgUrl) {
                                                            $mediaId = $this->addImageToMediaFromURL($catImgUrl, $context);
                                                        } else {
                                                            $mediaId = '';
                                                        }
                                                        $categoryData['mediaId'] = $mediaId;
                                                    } else {
                                                        $categoryData['mediaId'] = '';
                                                    }
                                                    //find cms id based on default english language
                                                    if (isset($res->ProductGroupDetails->UDF_PG_ProductGroupLayout->ValueDescription)) {
                                                        $languageKey = $this->getDefaultLanguageKey($context);
                                                        $cmsPageId = $this->findCMSName($res->ProductGroupDetails->UDF_PG_ProductGroupLayout->ValueDescription->$languageKey, $context);
                                                        if ($cmsPageId) {
                                                            $categoryData['cmsPageId'] = $cmsPageId;
                                                        }
                                                    } else {
                                                        $categoryData['cmsPageId'] = null;
                                                    }
                                                    $categoryData['visible'] = $res->ProductGroupDetails->UDF_PG_ShowInNav->Value;
                                                    $productDatas = $res->PublicationNodeRecordLinks;
                                                    if ($productDatas) {
                                                        $productCodes = array();
                                                        $KProductCodes = array();
                                                        foreach ($productDatas as $productData) {
                                                            $productId = $this->getProductID($productData->ProductCode->Value);
                                                            $SequenceNoP = $productData->SequenceNo->Value;
                                                            if ($productId) {
                                                                $productCodes[] = $productId;
                                                                $KProductCodes[] = array(
                                                                    'productId' => $productId,
                                                                    'position' => $SequenceNoP
                                                                );
                                                            }
                                                        }
                                                        $categoryData['products'] = $productCodes;
                                                        $categoryData['kplngiPositions'] = $KProductCodes;
                                                    }

                                                    $checkUpdatedProduct = $this->checkPimCategory($categoryData, $salesChannelId);
                                                    if (empty($checkUpdatedProduct) || $checkUpdatedProduct == null) {
                                                        $categoryID = $this->categoryInsert($SequenceNo, $cat_Array, $categoryData, $cron, $salesChannelId, $context);
                                                    } else {
                                                        $categoryID = $checkUpdatedProduct->getcategoryId();
                                                    }

                                                    file_put_contents(
                                                        "CategoryImportLog.txt",
                                                        date("l jS \of F Y h:i:s A") . "> " . $categoryID . " generated category is import\n",
                                                        FILE_APPEND
                                                    );
                                                    $cat_Array[$SequenceNo][] = $categoryID;
                                                    $ChildNodes = $res->ChildNodes;
                                                    if ($ChildNodes) {
                                                        $this->insertProductGroupRecursive($cat_Array, $categoryID, $ChildNodes, $cron, $salesChannelId, $context);
                                                    }
                                                }
                                                if ($Origin == 'Manual' && $res->Description) {
                                                    if ($Node == 99) {
                                                        $subCategoryID = $mainCategoryID;
                                                    }
                                                    $MCategoryCode = $res->Code->Value;
                                                    $MCategoryName = $res->Description->Value;
                                                    $MCheckCategoryExist = $this->checkCategoryExist($MCategoryName, $subCategoryID, $MCategoryCode, $context);

                                                    $MCategoryData = array();

                                                    if ($MCheckCategoryExist) {
                                                        $MCategoryData['id'] = $MCheckCategoryExist;
                                                    } else {
                                                        $MCategoryData['id'] = Uuid::randomHex();
                                                    }
                                                    $MCategoryData['code'] = $res->Code->Value;
                                                    $MCategoryData['parentId'] = $subCategoryID;
                                                    $MCategoryData['lastModificationDateTime'] = $res->LastModificationDateTime->Value;
                                                    $MCommercialDescription = $res->FullDescription->Value;


                                                    $MCategoryData['translations'] = $this->setCategoryTranslation($MCategoryName, $MCommercialDescription, $context);
                                                    //check image exist or not in shopware if not exist so add
                                                    if ($res->Image) {
                                                        $MCatImgUrl = self::DOMAIN . $res->Image->Value;
                                                        if ($MCatImgUrl) {
                                                            $MMediaId = $this->addImageToMediaFromURL($MCatImgUrl, $context);
                                                        } else {
                                                            $MMediaId = '';
                                                        }
                                                        $MCategoryData['mediaId'] = $MMediaId;
                                                    }

                                                    //find cms id based on default english language
                                                    if (isset($res->UDF_PubNode_Layout->ValueDescription)) {
                                                        $languageKey = $this->getDefaultLanguageKey($context);
                                                        $MCmsPageId = $this->findCMSName($res->UDF_PubNode_Layout->ValueDescription->$languageKey, $context);
                                                        if ($MCmsPageId) {
                                                            $MCategoryData['cmsPageId'] = $MCmsPageId;
                                                        }
                                                    } else {
                                                        $MCategoryData['cmsPageId'] = null;
                                                    }

                                                    $MCategoryData['visible'] = $res->UDF_PubNode_ShowInNav->Value;

                                                    $MProductDatas = $res->PublicationNodeRecordLinks;
                                                    if ($MProductDatas) {
                                                        $MProductCodes = array();
                                                        $MKProductCodes = array();
                                                        foreach ($MProductDatas as $MProductData) {
                                                            $MProductId = $this->getProductID($MProductData->ProductCode->Value);
                                                            $MSequenceNo = $MProductData->SequenceNo->Value;
                                                            if ($MProductId) {
                                                                $MProductCodes[] = $MProductId;
                                                                $MKProductCodes[] = array(
                                                                    'productId' => $MProductId,
                                                                    'position' => $MSequenceNo
                                                                );
                                                            }
                                                        }
                                                        $MCategoryData['products'] = $MProductCodes;
                                                        $MCategoryData['kplngiPositions'] = $MKProductCodes;
                                                    }

                                                    $checkUpdatedProduct = $this->checkPimCategory($MCategoryData, $salesChannelId);
                                                    if (empty($checkUpdatedProduct) || $checkUpdatedProduct == null) {
                                                        $MCategoryID = $this->categoryInsert($SequenceNo, $cat_Array, $MCategoryData, $cron, $salesChannelId, $context);
                                                    } else {
                                                        $MCategoryID = $checkUpdatedProduct->getcategoryId();
                                                    }
                                                    file_put_contents(
                                                        "CategoryImportLog.txt",
                                                        date("l jS \of F Y h:i:s A") . "> " . $MCategoryID . " generated category is import\n",
                                                        FILE_APPEND
                                                    );
                                                    $cat_Array[$SequenceNo][] = $MCategoryID;

                                                    $MChildNodes = $res->ChildNodes;
                                                    if ($MChildNodes) {
                                                        $this->insertProductGroupRecursive($cat_Array, $MCategoryID, $MChildNodes, $cron, $salesChannelId, $context);
                                                    }
                                                }
                                                $counter++;
                                            }
                                        }
                                    }
                                }
                            }
                            file_put_contents(
                                "CategoryImportLog.txt",
                                date("l jS \of F Y h:i:s A") . "> " . $PublicationCode . " End Import Category\n",
                                FILE_APPEND
                            );

                            //reset counter for sequence
                            $counter = $last_counter = $count99 = 1;
                            $cat_Array = [];

                            //Manage Sequence Concept
                            file_put_contents(
                                "CategoryImportLog.txt",
                                date("l jS \of F Y h:i:s A") . "> " . $PublicationCode . " Start Manage Sequence\n",
                                FILE_APPEND
                            );
                            foreach ($subCategories as $subCategoryID => $subCategory) {
                                if (trim($subCategory->getName()) == 'Product' || trim($subCategory->getName()) == 'Products' || trim($subCategory->getName()) == 'Producten' || trim($subCategory->getName()) == 'Izdelki' || trim($subCategory->getName()) == 'Termékek' || trim($subCategory->getName()) == 'Produkte' || trim($subCategory->getName()) == 'Productos' || trim($subCategory->getName()) == 'Proizvodi' || trim($subCategory->getName()) == 'Produse' || trim($subCategory->getName()) == 'Produkty' || trim($subCategory->getName()) == 'Prodotti') {
                                    if ($subCategory->getafterCategoryId() != null) {
                                        $mainData = [
                                            'id' => $subCategory->getId(),
                                            'afterCategoryId' => NULL
                                        ];
                                        $this->categoryRepository->upsert([$mainData], $context);
                                    }
                                    $getTotalCategory = $this->getpimAPIdata($categoryURL . $apiParameters . $apiKey . '&filter=PublicationCode=' . $PublicationCode);
                                    if (isset($getTotalCategory->Count)) {
                                        $perPage = $getTotalCategory->Count;
                                        $counter = 1;
                                        $apiUrl = $categoryURL . '/' . $counter . '/' . $perPage . $apiParameters . $apiKey . '&filter=PublicationCode=' . $PublicationCode;
                                        $categoryAPIData = $this->getpimAPIdata($apiUrl);
                                        if ($categoryAPIData->PublicationNodes) {
                                            foreach ($categoryAPIData->PublicationNodes as $res) {

                                                $Origin = $res->Origin->Value;
                                                $Node = $res->Code->Value;
                                                if ($Node == 99) {
                                                    $cat_Array = array();
                                                }
                                                $SequenceNo = $res->ProductGroupDetails->Level->Value ?? $count99;

                                                if ($Origin == 'Generated' && $res->ProductGroupDetails) {

                                                    if ($Node == 99) {
                                                        $subCategoryID = $mainCategoryID;
                                                    }
                                                    $categoryCode = $res->Code->Value;
                                                    $categoryName = $res->ProductGroupDetails->Description->Value;
                                                    $checkCategoryExist = $this->checkCategoryExist($categoryName, $subCategoryID, $categoryCode, $context);

                                                    if ($checkCategoryExist) {
                                                        $categoryData = array();
                                                        $categoryData['id'] = $checkCategoryExist;
                                                        $categoryData['parentId'] = $subCategoryID;
                                                        $categoryID = $this->categoryInsertSeq($SequenceNo, $cat_Array, $categoryData, $context);
                                                        $cat_Array[$SequenceNo][] = $categoryID;
                                                        $ChildNodes = $res->ChildNodes;
                                                        if ($ChildNodes) {
                                                            $count99++;
                                                            $ChildNodes = $this->arrayCustomMultiSort($ChildNodes);
                                                            $this->insertProductGroupRecursiveSeq($count99, $cat_Array, $categoryID, $ChildNodes, $context);
                                                        }
                                                    }
                                                    $checkUpdatedProduct = $this->checkPimCategorySeq($checkCategoryExist, $salesChannelId);
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
                                                    $MCheckCategoryExist = $this->checkCategoryExist($MCategoryName, $subCategoryID, $categoryCode, $context);
                                                    if ($MCheckCategoryExist) {

                                                        $MCategoryData = array();
                                                        $MCategoryData['id'] = $MCheckCategoryExist;
                                                        $MCategoryData['parentId'] = $subCategoryID;
                                                        $MCategoryID = $this->categoryInsertSeq($SequenceNo, $cat_Array, $MCategoryData, $context);
                                                        $cat_Array[$SequenceNo][] = $MCategoryID;
                                                        $MChildNodes = $res->ChildNodes;
                                                        if ($MChildNodes) {
                                                            $count99++;
                                                            $MChildNodes = $this->arrayCustomMultiSort($MChildNodes);
                                                            $this->insertProductGroupRecursiveSeq($count99, $cat_Array, $MCategoryID, $MChildNodes, $context);
                                                        }
                                                    }
                                                    $checkUpdatedProduct = $this->checkPimCategorySeq($MCheckCategoryExist, $salesChannelId);
                                                    if (!empty($checkUpdatedProduct) || $checkUpdatedProduct != null) {
                                                        $submainData = [
                                                            'id' => $checkUpdatedProduct->getcategoryId(),
                                                            'afterCategoryId' => $subCategory->getId()
                                                        ];
                                                        $this->categoryRepository->upsert([$submainData], $context);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            file_put_contents(
                                "CategoryImportLog.txt",
                                date("l jS \of F Y h:i:s A") . "> " . $PublicationCode . " End Manage Sequence\n",
                                FILE_APPEND
                            );
                            //reset counter for delete
                            $allCategoryData = $cat_Array = $AllCategoty = [];
                            $counter = $last_counter = $count99 = 1;

                            //Manage Delete Concept
                            file_put_contents(
                                "CategoryImportLog.txt",
                                date("l jS \of F Y h:i:s A") . "> " . $PublicationCode . " Start Delete\n",
                                FILE_APPEND
                            );
                            foreach ($subCategories as $subCategoryID => $subCategory) {
                                if (trim($subCategory->getName()) == 'Product' || trim($subCategory->getName()) == 'Products' || trim($subCategory->getName()) == 'Producten' || trim($subCategory->getName()) == 'Izdelki' || trim($subCategory->getName()) == 'Termékek' || trim($subCategory->getName()) == 'Produkte' || trim($subCategory->getName()) == 'Productos' || trim($subCategory->getName()) == 'Proizvodi' || trim($subCategory->getName()) == 'Produse' || trim($subCategory->getName()) == 'Produkty' || trim($subCategory->getName()) == 'Prodotti') {
                                    $getTotalCategory = $this->getpimAPIdata($categoryURL . $apiParameters . $apiKey . '&filter=PublicationCode=' . $PublicationCode);
                                    if (isset($getTotalCategory->Count)) {
                                        $last_counter = $getTotalCategory->Count;
                                        $apiUrl = $categoryURL . '/' . 1 . '/' . $last_counter . $apiParameters . $apiKey . '&filter=PublicationCode=' . $PublicationCode;
                                        $categoryAPIData = $this->getpimAPIdata($apiUrl);
                                        if ($categoryAPIData->PublicationNodes) {
                                            foreach ($categoryAPIData->PublicationNodes as $res) {
                                                $Origin = $res->Origin->Value;
                                                $Node = $res->Code->Value;
                                                if ($Origin == 'Generated' && $res->ProductGroupDetails) {
                                                    if ($Node == 99) {
                                                        $subCategoryID = $mainCategoryID;
                                                    }
                                                    $categoryName = $res->ProductGroupDetails->Description->Value;
                                                    $categoryCode = $res->Code->Value;
                                                    $checkCategoryExist = $this->checkCategoryExist($categoryName, $subCategoryID, $categoryCode, $context);
                                                    if ($checkCategoryExist) {
                                                        $allCategoryData[] = $checkCategoryExist;
                                                        $categoryId = $checkCategoryExist;
                                                        $ChildNodes = $res->ChildNodes;
                                                        if ($ChildNodes) {
                                                            $subarray = $this->checkCategoryChildId($ChildNodes, $categoryId, $context, $allCategoryData);
                                                            $allCategoryData = array_merge($allCategoryData, $subarray);
                                                        }
                                                    }
                                                }

                                                if ($Origin == 'Manual' && $res->Description) {
                                                    if ($Node == 99) {
                                                        $subCategoryID = $mainCategoryID;
                                                    }
                                                    $MCategoryName = $res->Description->Value;
                                                    $categoryCode = $res->Code->Value;
                                                    $MCheckCategoryExist = $this->checkCategoryExist($MCategoryName, $subCategoryID, $categoryCode, $context);
                                                    if ($MCheckCategoryExist) {
                                                        $allCategoryData[] = $MCheckCategoryExist;
                                                        $MCategoryId = $MCheckCategoryExist;
                                                        $MChildNodes = $res->ChildNodes;
                                                        if ($MChildNodes) {
                                                            $subarray = $this->checkCategoryChildId($MChildNodes, $MCategoryId, $context, $allCategoryData);
                                                            $allCategoryData = array_merge($allCategoryData, $subarray);
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            $allCategoryData = array_unique($allCategoryData);
                            $dif = $this->checkPimCategoryData($salesChannelId, $context);
                            $finalDiff = array_diff($dif, $allCategoryData);
                            if (!empty($finalDiff)) {
                                $this->removeCategoryIds($finalDiff, $context);
                                file_put_contents(
                                    "CategoryImportLog.txt",
                                    date("l jS \of F Y h:i:s A") . "> Category Deleted Successfully\n",
                                    FILE_APPEND
                                );
                            }

                            $categoryCronSalesChannelData = [
                                'id' => Uuid::randomHex(),
                                'salesChannelId' => $salesChannelId,
                                'lastUsageAt' => date("Y-m-d"),
                            ];
                            $testResult = $this->category_cron_saleschannel->upsert([$categoryCronSalesChannelData], $context);
                            file_put_contents(
                                "CategoryImportLog.txt",
                                date("l jS \of F Y h:i:s A") . "> All SalesChannel Success Nothing to Delete\n",
                                FILE_APPEND
                            );
                        }
                        file_put_contents(
                            "CategoryImportLog.txt",
                            date("l jS \of F Y h:i:s A") . "> " . $PublicationCode . " Sales Channel\n",
                            FILE_APPEND
                        );
                    } else {
                        //skip sales channel
                        file_put_contents(
                            "CategoryImportLog.txt",
                            date("l jS \of F Y h:i:s A") . "> " . $PublicationCode . " Skip Sales Channel\n",
                            FILE_APPEND
                        );
                    }
                }
            }

        }
        echo"Success";
        return new JsonResponse([
            'type' => 'success',
            'message' => 'Success',
        ]);
    }

    //get sales channel using id
    public function getSalesChannelUsingId($context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active',true));
        return $this->salesChannelRepository->search($criteria,$context)->getEntities()->getElements();
    }


    //get all child
    public function getChildren(string $parentId, Context $context): array
    {
        //set up the criteria for the search
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('parentId', $parentId));
        return $this->categoryRepository->search($criteria, $context)->getEntities()->getElements();
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

    //check category
    public function checkCategoryExist(object $categoryName, string $parentId, $categoryCode, Context $context): ?string
    {
        //set up the criteria for the search
        $languageKey = $this->getDefaultLanguageKey($context);
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('parentId', $parentId));
        $criteria->addFilter(new EqualsFilter('customFields.custom_pim_category_code',$categoryCode));
        $id = $this->categoryRepository->searchIds($criteria, $context)->firstId();
        if($id != null){
            return $id;
        }
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $categoryName->$languageKey));
        $criteria->addFilter(new EqualsFilter('parentId', $parentId));
        $id = $this->categoryRepository->searchIds($criteria, $context)->firstId();
        return $id;
    }

    public function getDefaultLanguageKey (Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id',$context->getlanguageIdChain()[0]));
        $criteria->addAssociation('locale');
        $data = $this->languageRepository->search($criteria,$context)->first();
        $languageKey = null;
        if($data->getName() == 'English'){
            $languageKey = 'en';
        }
        if($data->getName() == 'Dutch' || $data->getName() == 'Nederlands'){
            $languageKey = 'nl';
        }
        if($data->getName() == 'Deutsch'){
            $languageKey = 'de';
        }
        if($data->getName() == 'Magyar'){
            $languageKey = 'hu';
        }
        if($data->getName() == 'Italiano'){
            $languageKey = 'it';
        }
        if($data->getName() == 'Croatian'){
            $languageKey = 'hr';
        }
        if($data->getName() == 'Polski'){
            $languageKey = 'pl';
        }
        if($data->getName() == 'Română'){
            $languageKey = 'ro';
        }
        if($data->getName() == 'Slovenian'){
            $languageKey = 'sl';
        }
        if($data->getName() == 'Español'){
            $languageKey = 'es';
        }
        if($data->getName() == 'Čeština'){
            $languageKey = 'cs';
        }
        return $languageKey;
    }


    // Check the Language
    public function setCategoryTranslation(object $categoryName,object $CommercialDescription,Context $context): array
    {
        $languageData = array();
        $criteria = new Criteria();
        $datas = $this->languageRepository->search($criteria, $context)->getElements();

        foreach ($datas as $data) {
            if($data->getName() == 'English'){
                $languageKey = 'en';
                $languageData[$data->getId()]['name'] = $categoryName->$languageKey;
                $languageData[$data->getId()]['description'] = $CommercialDescription->$languageKey;
            }
            if($data->getName() == 'Dutch' || $data->getName() == 'Nederlands'){
                $languageKey = 'nl';
                $languageData[$data->getId()]['name'] = $categoryName->$languageKey;
                $languageData[$data->getId()]['description'] = $CommercialDescription->$languageKey;
            }
            if($data->getName() == 'Deutsch'){
                $languageKey = 'de';
                $languageData[$data->getId()]['name'] = $categoryName->$languageKey;
                $languageData[$data->getId()]['description'] = $CommercialDescription->$languageKey;
            }
            if($data->getName() == 'Magyar'){
                $languageKey = 'hu';
                $languageData[$data->getId()]['name'] = $categoryName->$languageKey;
                $languageData[$data->getId()]['description'] = $CommercialDescription->$languageKey;
            }
            if($data->getName() == 'Italiano'){
                $languageKey = 'it';
                $languageData[$data->getId()]['name'] = $categoryName->$languageKey;
                $languageData[$data->getId()]['description'] = $CommercialDescription->$languageKey;
            }
            if($data->getName() == 'Croatian'){
                $languageKey = 'hr';
                $languageData[$data->getId()]['name'] = $categoryName->$languageKey;
                $languageData[$data->getId()]['description'] = $CommercialDescription->$languageKey;
            }
            if($data->getName() == 'Polski'){
                $languageKey = 'pl';
                $languageData[$data->getId()]['name'] = $categoryName->$languageKey;
                $languageData[$data->getId()]['description'] = $CommercialDescription->$languageKey;
            }
            if($data->getName() == 'Română'){
                $languageKey = 'ro';
                $languageData[$data->getId()]['name'] = $categoryName->$languageKey;
                $languageData[$data->getId()]['description'] = $CommercialDescription->$languageKey;
            }
            if($data->getName() == 'Slovenian'){
                $languageKey = 'sl';
                $languageData[$data->getId()]['name'] = $categoryName->$languageKey;
                $languageData[$data->getId()]['description'] = $CommercialDescription->$languageKey;
            }
            if($data->getName() == 'Español'){
                $languageKey = 'es';
                $languageData[$data->getId()]['name'] = $categoryName->$languageKey;
                $languageData[$data->getId()]['description'] = $CommercialDescription->$languageKey;
            }
            if($data->getName() == 'Čeština'){
                $languageKey = 'cs';
                $languageData[$data->getId()]['name'] = $categoryName->$languageKey;
                $languageData[$data->getId()]['description'] = $CommercialDescription->$languageKey;
            }
        }
        return $languageData;
    }

    //add image
    public function addImageToMediaFromURL (string $imageUrl, Context $context): ?string
    {
        $mediaId = null;
        //parse the URL
        $filePathParts = explode('/', $imageUrl);
        $fileNameParts = explode('.', array_pop($filePathParts));
        //get the file name and extension
        $fileName = $fileNameParts[0];
        $fileExtension = $fileNameParts[1];

        if ($fileName && $fileExtension) {
            //copy the file from the URL to the newly created local temporary file
            $filePath = tempnam(sys_get_temp_dir(),$fileName);
            file_put_contents($filePath, @file_get_contents($imageUrl));
            //create media record from the image
            $mediaId = $this->createMediaFromFile($filePath, $fileName, $fileExtension, $context);
        }
        return $mediaId;
    }

    //create media
    private function createMediaFromFile(string $filePath, string $fileName,string $fileExtension, Context $context): ?string
    {
        $mediaId = null;
        //get additional info on the file
        $fileSize = filesize($filePath);
        $mimeType = mime_content_type($filePath);
        //create and save new media file to the Shopware's media library
        try {
            $mediaFile = new MediaFile($filePath, $mimeType, $fileExtension, $fileSize);
            $mediaId = $this->mediaService->createMediaInFolder('Product', $context, false);
            $this->fileSaver->persistFileToMedia($mediaFile,$fileName,$mediaId,$context);
        }
        catch (DuplicatedMediaFileNameException | Exception $e) {
            $mediaId = $this->mediaCleanup($mediaId, $context);
        }
        //find media in shopware media
        if(empty($mediaId)){
            $mediaId = $this->checkImageExist($fileName, $context);
        }
        return $mediaId;
    }

    //delete media
    private function mediaCleanup (string $mediaId, Context $context)
    {
        $this->mediaRepository->delete([['id' => $mediaId]], $context);
        return null;
    }

    //check image exist in media
    private function checkImageExist (string $fileName, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('fileName',$fileName));
        $media_object = $this->mediaRepository->searchIds($criteria, $context);
        return $media_object->firstId();
    }

    //find Layout assignment name recursive
    public function findCMSName (string $cmsPageName, Context $context): ?string
    {
        //set up the criteria for the search
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $cmsPageName));
        $cmsPageID = $this->cmsPageRepository->searchIds($criteria, $context)->firstId();
        if($cmsPageID){
            return $cmsPageID;
        }else{
            return $this->findCMSName('Default category layout',$context);
        }
    }

    // Check the Product
    public function getProductID($productNumber = null)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productNumber',$productNumber));
        $productArray = $this->productRepository->searchIds($criteria, Context::createDefaultContext())->getIds();
        return $productArray[0] ?? '';
    }

    public function checkPimCategory(array $categoryData,$salesChannelId)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('categoryId', $categoryData['id']));
        $criteria->addFilter(new EqualsFilter('categoryCode', $categoryData['code']));
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));
        $criteria->addFilter(new EqualsFilter('lastUsageAt', $categoryData['lastModificationDateTime']));
        return $this->pimCategoryRepository->search($criteria, Context::createDefaultContext())->first();
    }

    // Insert Category
    public function categoryInsert($SequenceNo,$cat_Array,array $categoryData,$cron, $salesChannelId, Context $context): string
    {
        $categoryID = $categoryData['id'];

        $data = [
            'id'            => $categoryData['id'],
            'parentId'      => $categoryData['parentId'],
            'translations'  => $categoryData['translations'],
            'cmsPageId'     => $categoryData['cmsPageId'],
            'visible'       => $categoryData['visible'],
            'customFields' => ['custom_pim_category_code' => $categoryData['code']],
        ];

        //afterCategoryId
        if(isset($cat_Array) && isset($cat_Array[$SequenceNo])) {
            if (end($cat_Array[$SequenceNo])) {
                $data['afterCategoryId'] = end($cat_Array[$SequenceNo]);
            }
        }else{
            $data['afterCategoryId'] = NULL;
        }

        if(isset($categoryData['mediaId']) && $categoryData['mediaId']) {
            $data['mediaId'] = $categoryData['mediaId'];
        }
        file_put_contents(
            "CategoryImportLog.txt",
            date("l jS \of F Y h:i:s A")."> ".$categoryID." generated category is import\n",
            FILE_APPEND
        );

        $this->categoryRepository->upsert([$data], $context);

        //add product id in category
        if(isset($categoryData['products']) && $categoryID){
            foreach($categoryData['products'] as $productId){
                $this->setCategoryId($productId,$categoryID,$context);
            }
        }

        //add position in kpi
        if(isset($categoryData['kplngiPositions']) && $categoryID){
            foreach($categoryData['kplngiPositions'] as $product){
                $this->setkplngiPosition($product['productId'],$categoryID,$product['position'],$context);
            }
        }

        $PimCategoryID = $this->checkPimCategoryID($categoryData, $salesChannelId);

        $pimcategoryDatas = [
            'id' => !empty($PimCategoryID) ? $PimCategoryID->getId() : Uuid::randomHex(),
            'categoryId' => $categoryData['id'],
            'categoryCode'=>$categoryData['code'],
            'salesChannelId' => $salesChannelId,
            'lastUsageAt' => $categoryData['lastModificationDateTime'],
        ];
        $this->pimCategoryRepository->upsert([$pimcategoryDatas], $context);
        return $categoryID;

    }

    //update category id in product
    public function setCategoryId ($productId,$categoryId,$context)
    {
        $this->productRepository->update(
            [
                [
                    'id' => $productId,
                    'categories' => [
                        [ 'id' => $categoryId ]
                    ]
                ]
            ],
            $context);
    }

    //update category id in product
    public function setkplngiPosition ($productId,$categoryId,$position,$context)
    {
        $checkKPCategoryExist = $this->checkKPCategoryExist($categoryId,$context);
        if(!$checkKPCategoryExist){
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

        $checkProductCategoryPositionExist = $this->checkproductCategoryPositionExist($productId,$categoryId,$context);
        $this->productCategoryPositionRepository->upsert(
            [
                [
                    'id' => $checkProductCategoryPositionExist?$checkProductCategoryPositionExist:Uuid::randomHex(),
                    'productId' => $productId,
                    'categoryId' => $categoryId,
                    'position' => $position
                ]
            ],
            $context
        );
    }

    //check category
    public function checkproductCategoryPositionExist (string $productId,string $categoryId,Context $context): ?string
    {
        //set up the criteria for the search
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('categoryId', $categoryId));
        $criteria->addFilter(new EqualsFilter('productId', $productId));
        return $this->productCategoryPositionRepository->searchIds($criteria, $context)->firstId();
    }

    //check category
    public function checkKPCategoryExist (string $categoryId,Context $context): ?string
    {
        //set up the criteria for the search
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('categoryId', $categoryId));
        return $this->kplngi_orderactive->searchIds($criteria, $context)->firstId();
    }

    public function checkPimCategoryID(array $categoryData, $salesChannelId)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('categoryId', $categoryData['id']));
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));
        return $this->pimCategoryRepository->search($criteria, Context::createDefaultContext())->first();
    }

    public function insertProductGroupRecursive($cat_Array,$subCategoryID,$ChildNodes,$cron,$salesChannelId, $context){

        foreach($ChildNodes as $res){
            $Origin = $res->Origin->Value;

            $SequenceNo = $res->ProductGroupDetails->Level->Value ?? '0';

            if($Origin == 'Generated' && $res->ProductGroupDetails){
                $categoryCode = $res->Code->Value;
                $categoryName = $res->ProductGroupDetails->Description->Value;

                $checkCategoryExist = $this->checkCategoryExist($categoryName, $subCategoryID, $categoryCode, $context);

                $categoryData = array();

                if(isset($checkCategoryExist)) {
                    $categoryData['id'] = $checkCategoryExist;
                }else {
                    $categoryData['id'] = Uuid::randomHex();
                }
                $categoryData['code'] = $res->Code->Value;
                $categoryData['parentId'] = $subCategoryID;
                $categoryData['lastModificationDateTime'] = $res->ProductGroupDetails->LastModificationDateTime->Value;
                $CommercialDescription = $res->ProductGroupDetails->CommercialDescription->Value;

                $categoryData['translations'] = $this->setCategoryTranslation($categoryName, $CommercialDescription, $context);

                //check image exist or not in shopware if not exist so add
                if ($res->ProductGroupDetails->Image) {
                    $catImgUrl = self::DOMAIN . $res->ProductGroupDetails->Image->Value;
                    if ($catImgUrl) {
                        $mediaId = $this->addImageToMediaFromURL($catImgUrl, $context);
                    } else {
                        $mediaId = '';
                    }
                    $categoryData['mediaId'] = $mediaId;
                } else {
                    $categoryData['mediaId'] = '';
                }

                //find cms id based on default english language
                if (isset($res->ProductGroupDetails->UDF_PG_ProductGroupLayout->ValueDescription)) {
                    $languageKey = $this->getDefaultLanguageKey($context);
                    $cmsPageId = $this->findCMSName($res->ProductGroupDetails->UDF_PG_ProductGroupLayout->ValueDescription->$languageKey, $context);
                    if ($cmsPageId) {
                        $categoryData['cmsPageId'] = $cmsPageId;
                    }
                } else {
                    $categoryData['cmsPageId'] = null;
                }

                $categoryData['visible'] = $res->ProductGroupDetails->UDF_PG_ShowInNav->Value;

                $productDatas = $res->PublicationNodeRecordLinks;
                if ($productDatas) {
                    $productCodes = array();
                    $KProductCodes = array();
                    foreach ($productDatas as $productData) {
                        $productId = $this->getProductID($productData->ProductCode->Value);

                        $SequenceNoP = $productData->SequenceNo?$productData->SequenceNo->Value:950;
                        if ($productId) {
                            $productCodes[] = $productId;
                            $KProductCodes[] = array(
                                'productId' => $productId,
                                'position' => $SequenceNoP
                            );
                        }
                    }
                    $categoryData['products'] = $productCodes;
                    $categoryData['kplngiPositions'] = $KProductCodes;
                }

                $checkUpdatedProduct = $this->checkPimCategory($categoryData, $salesChannelId);
                if (empty($checkUpdatedProduct) || $checkUpdatedProduct == null) {
                    $categoryID = $this->categoryInsert($SequenceNo, $cat_Array, $categoryData, $cron,$salesChannelId, $context);
                }else{
                    $categoryID = $checkUpdatedProduct->getcategoryId();
                }
                file_put_contents(
                    "CategoryImportLog.txt",
                    date("l jS \of F Y h:i:s A")."> ".$categoryID." Cron Category Import\n",
                    FILE_APPEND
                );
                $cat_Array[$SequenceNo][] = $categoryID;
                $ChildNodes = $res->ChildNodes;
                if ($ChildNodes) {
                    $this->insertProductGroupRecursive($cat_Array, $categoryID, $ChildNodes, $cron, $salesChannelId, $context);
                }
            }

            if($Origin == 'Manual' && $res->Description){

                $MCategoryCode = $res->Code->Value;
                $MCategoryName = $res->Description->Value;
                $MCheckCategoryExist = $this->checkCategoryExist($MCategoryName, $subCategoryID, $MCategoryCode, $context);

                $MCategoryData = array();

                if ($MCheckCategoryExist) {
                    $MCategoryData['id'] = $MCheckCategoryExist;
                }else{
                    $MCategoryData['id'] = Uuid::randomHex();
                }

                $MCategoryData['code']  = $res->Code->Value;
                $MCategoryData['parentId'] = $subCategoryID;
                $MCategoryData['lastModificationDateTime'] = $res->LastModificationDateTime->Value;
                $MCommercialDescription = $res->FullDescription->Value;

                $MCategoryData['translations'] = $this->setCategoryTranslation($MCategoryName, $MCommercialDescription, $context);

                //check image exist or not in shopware if not exist so add
                if($res->Image) {
                    $MCatImgUrl = self::DOMAIN . $res->Image->Value;
                    if ($MCatImgUrl) {
                        $MMediaId = $this->addImageToMediaFromURL($MCatImgUrl, $context);
                    } else {
                        $MMediaId = '';
                    }
                    $MCategoryData['mediaId'] = $MMediaId;
                }

                //find cms id based on default english language
                if(isset($res->UDF_PubNode_Layout->ValueDescription)) {
                    $languageKey = $this->getDefaultLanguageKey($context);
                    $MCmsPageId = $this->findCMSName($res->UDF_PubNode_Layout->ValueDescription->$languageKey, $context);
                    if ($MCmsPageId) {
                        $MCategoryData['cmsPageId'] = $MCmsPageId;
                    }
                }else{
                    $MCategoryData['cmsPageId'] = null;
                }

                $MCategoryData['visible'] = $res->UDF_PubNode_ShowInNav->Value;

                $MProductDatas = $res->PublicationNodeRecordLinks;
                if($MProductDatas){
                    $MProductCodes = array();
                    $MKProductCodes = array();
                    foreach ($MProductDatas as $MProductData){
                        $MProductId =  $this->getProductID($MProductData->ProductCode->Value);
                        $MSequenceNo = $MProductData->SequenceNo->Value;
                        if($MProductId){
                            $MProductCodes[] = $MProductId;
                            $MKProductCodes[] = array(
                                'productId'=>$MProductId,
                                'position'=>$MSequenceNo
                            );
                        }
                    }
                    $MCategoryData['products'] = $MProductCodes;
                    $MCategoryData['kplngiPositions'] = $MKProductCodes;
                }

                $checkUpdatedProduct = $this->checkPimCategory($MCategoryData, $salesChannelId);
                if (empty($checkUpdatedProduct) || $checkUpdatedProduct == null) {
                    $MCategoryID = $this->categoryInsert($SequenceNo,$cat_Array,$MCategoryData,$cron,$salesChannelId, $context);
                }else{
                    $MCategoryID = $checkUpdatedProduct->getcategoryId();
                }
                file_put_contents(
                    "CategoryImportLog.txt",
                    date("l jS \of F Y h:i:s A")."> ".$MCategoryID." Cron Category Import\n",
                    FILE_APPEND
                );
                $cat_Array[$SequenceNo][] = $MCategoryID;

                $MChildNodes = $res->ChildNodes;
                if($MChildNodes) {
                    $this->insertProductGroupRecursive($cat_Array,$MCategoryID, $MChildNodes,$cron,$salesChannelId, $context);
                }

            }
        }
    }

    // Insert Category
    public function categoryInsertSeq($SequenceNo,$cat_Array,array $categoryData,Context $context): string
    {
        $data = [
            'id'            => $categoryData['id'],
            'parentId'      => $categoryData['parentId']
        ];
        //afterCategoryId
        if(isset($cat_Array) && isset($cat_Array[$SequenceNo])) {
            if (end($cat_Array[$SequenceNo])) {
                $data['afterCategoryId'] = end($cat_Array[$SequenceNo]);
            }
        }else{
            $data['afterCategoryId'] = NULL;
        }
        $this->categoryRepository->upsert([$data], $context);
        return $categoryData['id'];
    }

    public function arrayCustomMultiSort($ChildNodes){
        $tempArray = array();
        foreach($ChildNodes as $childNode){
            $tempArray[$childNode->SequenceNo->Value] = $childNode;
        }
        ksort($tempArray);
        return $tempArray;
    }


    public function insertProductGroupRecursiveSeq($count99,$cat_Array,$subCategoryID,$ChildNodes,$context){

        foreach($ChildNodes as $res){
            //echo "<pre>";print_r($cat_Array);echo "</pre>";
            $Origin = $res->Origin->Value;

            if(isset($res->ProductGroupDetails->Level->Value)) {
                $SequenceNo = $res->ProductGroupDetails->Level->Value;
            }else{
                $SequenceNo = $count99;
            }

            if($Origin == 'Generated' && $res->ProductGroupDetails){
                $categoryName = $res->ProductGroupDetails->Description->Value;
                $categoryCode = $res->Code->Value;
                $checkCategoryExist = $this->checkCategoryExist($categoryName,$subCategoryID,$categoryCode,$context);
                if($checkCategoryExist) {
                    $categoryData = array();
                    $categoryData['id'] = $checkCategoryExist;
                    $categoryData['parentId'] = $subCategoryID;
                    $CommercialDescription = $res->ProductGroupDetails->CommercialDescription->Value;
                    $categoryData['translations'] = $this->setCategoryTranslation($categoryName, $CommercialDescription, $context);
                    $categoryID = $this->categoryInsertSeq($SequenceNo,$cat_Array,$categoryData,$context);
                    $cat_Array[$SequenceNo][] = $categoryID;
                    $ChildNodes = $res->ChildNodes;

                    if($ChildNodes) {
                        $count99++;
                        $ChildNodes = $this->arrayCustomMultiSort($ChildNodes);
                        $this->insertProductGroupRecursiveSeq($count99,$cat_Array,$categoryID, $ChildNodes, $context);
                        $count99--;
                    }
                }
            }

            if($Origin == 'Manual' && $res->Description){
                $MCategoryName = $res->Description->Value;
                $categoryCode = $res->Code->Value;
                $MCheckCategoryExist = $this->checkCategoryExist($MCategoryName,$subCategoryID,$categoryCode,$context);
                if($MCheckCategoryExist) {
                    $MCategoryData = array();
                    $MCategoryData['id'] = $MCheckCategoryExist;
                    $MCategoryData['parentId'] = $subCategoryID;
                    $MCommercialDescription = $res->FullDescription->Value;
                    $MCategoryData['translations'] = $this->setCategoryTranslation($MCategoryName, $MCommercialDescription, $context);
                    $MCategoryID = $this->categoryInsertSeq($SequenceNo,$cat_Array,$MCategoryData,$context);
                    $cat_Array[$SequenceNo][] = $MCategoryID;
                    $MChildNodes = $res->ChildNodes;
                    if($MChildNodes) {
                        $count99++;
                        $MChildNodes = $this->arrayCustomMultiSort($MChildNodes);
                        $this->insertProductGroupRecursiveSeq($count99,$cat_Array,$MCategoryID, $MChildNodes, $context);
                        $count99--;
                    }
                }
            }
        }
    }

    public function checkPimCategorySeq($checkCategoryExist, $salesChannelId)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('categoryId', $checkCategoryExist));
        $criteria->addFilter(new EqualsFilter('categoryCode', "99"));
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));
        return $this->pimCategoryRepository->search($criteria, Context::createDefaultContext())->first();
    }

    public function checkCategoryChildId($ChildNodes,$subCategoryID,$context,$allCategoryData)
    {
        foreach ($ChildNodes as $res) {
            $Origin = $res->Origin->Value;
            if ($Origin == 'Generated' && $res->ProductGroupDetails) {
                $categoryCode = $res->Code->Value;
                $categoryName = $res->ProductGroupDetails->Description->Value;
                $checkCategoryExist = $this->checkCategoryExist($categoryName, $subCategoryID, $categoryCode, $context);
                if ($checkCategoryExist) {
                    $allCategoryData[] = $checkCategoryExist;
                    $categoryId = $checkCategoryExist;
                    $ChildNodes = $res->ChildNodes;
                    if ($ChildNodes) {
                        $subarray = $this->checkCategoryChildId($ChildNodes, $categoryId,$context,$allCategoryData);
                        $allCategoryData = array_merge($allCategoryData,$subarray);
                    }
                }
            }

            if ($Origin == 'Manual' && $res->Description) {
                $MCategoryName = $res->Description->Value;
                $categoryCode = $res->Code->Value;
                $MCheckCategoryExist = $this->checkCategoryExist($MCategoryName, $subCategoryID,$categoryCode, $context);
                if ($MCheckCategoryExist) {
                    $allCategoryData[] = $MCheckCategoryExist;
                    $MCategoryId = $MCheckCategoryExist;
                    $MChildNodes = $res->ChildNodes;
                    if ($MChildNodes) {
                        $subarray = $this->checkCategoryChildId($ChildNodes, $MCategoryId,$context,$allCategoryData);
                        $allCategoryData = array_merge($allCategoryData,$subarray);
                    }
                }
            }
        }
        return $allCategoryData;
    }


    public function checkPimCategoryData($salesChannelId,Context  $context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId',$salesChannelId));
        $Datas = $this->pimCategoryRepository->search($criteria, $context)->getElements();
        $allPimCategoryDataID = array();
        foreach ($Datas as $data) {
            $allPimCategoryDataID[] = $data->getCategoryId();
        }
        return $allPimCategoryDataID;
    }

    public function removeCategoryIds($finalDiff, Context $context)
    {
        foreach ($finalDiff as $categoryId) {
            $this->categoryRepository->delete([['id' => $categoryId]], $context);
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('categoryId',$categoryId));
            $Datas = $this->pimCategoryRepository->search($criteria, $context)->first();
            $this->pimCategoryRepository->delete([['id' => $Datas->id]], $context);
        }
    }

    //get all sales channel
    public function getAllSalesChannel($context): array
    {
        $criteria = new Criteria();
        return $this->salesChannelRepository->search($criteria,$context)->getElements();
    }

    public function checkUpdatedSalesChannel($date,$salesChannelId,$context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('lastUsageAt', $date));
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));
        $categoryObj = $this->category_cron_saleschannel->search($criteria,$context);
        return $categoryObj->getTotal();
    }



}
