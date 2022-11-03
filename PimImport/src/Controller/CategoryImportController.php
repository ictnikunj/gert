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
class CategoryImportController extends AbstractController
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
    private $productCategoryRepository;
    private $salesChannelRepository;
    private $pimCategoryRepository;
    private $productCategoryPositionRepository;
    private $kplngi_orderactive;

    const domain = "https://fluidmaster.compano.com";

    public function __construct(
        SystemConfigService       $systemConfigService,
        EntityRepositoryInterface $categoryRepository,
        EntityRepositoryInterface $languageRepository,
        EntityRepositoryInterface $mediaRepository,
        MediaService              $mediaService,
        FileSaver                 $fileSaver,
        EntityRepositoryInterface $cmsPageRepository,
        EntityRepositoryInterface $productRepository,
        EntityRepositoryInterface $productCategoryRepository,
        EntityRepositoryInterface $salesChannelRepository,
        EntityRepositoryInterface $pimCategoryRepository,
        EntityRepositoryInterface $productCategoryPositionRepository,
        EntityRepositoryInterface $kplngi_orderactive
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->categoryRepository = $categoryRepository;
        $this->languageRepository = $languageRepository;
        $this->mediaRepository = $mediaRepository;
        $this->mediaService = $mediaService;
        $this->fileSaver = $fileSaver;
        $this->cmsPageRepository = $cmsPageRepository;
        $this->productRepository = $productRepository;
        $this->productCategoryRepository = $productCategoryRepository;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->pimCategoryRepository = $pimCategoryRepository;
        $this->productCategoryPositionRepository = $productCategoryPositionRepository;
        $this->kplngi_orderactive = $kplngi_orderactive;
    }

    /**
     * @Route("/api/pim/categoryimport", name="api.action.pim.category.import", methods={"GET"})
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

                    //get two main hierarchy
                    $mainCategoryID = $getAllSalesChannel->getnavigationCategoryId();

                    $subCategories = $this->getChildren($mainCategoryID, $context);

                    if ($subCategories) {
                        foreach ($subCategories as $subCategoryID => $subCategory) {
                            //it should be added as new node after "PRODUCTS" in the category structure

                            if (trim($subCategory->getName()) == 'Product' || trim($subCategory->getName()) == 'Products' || trim($subCategory->getName()) == 'Producten' || trim($subCategory->getName()) == 'Izdelki' || trim($subCategory->getName()) == 'Termékek' || trim($subCategory->getName()) == 'Produkte' || trim($subCategory->getName()) == 'Productos' || trim($subCategory->getName()) == 'Proizvodi' || trim($subCategory->getName()) == 'Produse' || trim($subCategory->getName()) == 'Produkty' || trim($subCategory->getName()) == 'Prodotti') {
                                $getTotalCategory = $this->getpimAPIdata($categoryURL . $apiParameters . $apiKey . '&filter=PublicationCode=' . $PublicationCode);

                                if (isset($getTotalCategory->Count)) {
                                    $last_counter = $getTotalCategory->Count;

                                    if ($cron == 1) {
                                        //for cron
                                        $perPage = $last_counter;
                                        $counter = 1;
                                    } else {
                                        if (isset($_GET['counter'])) {
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

                                            if (isset($res->ProductGroupDetails->Level->Value)) {
                                                $SequenceNo = $res->ProductGroupDetails->Level->Value;
                                            } else {
                                                $SequenceNo = '0';
                                            }

                                            if ($cron == 1) {
                                                echo $Origin . '---' . $counter . '----' . $apiUrl;
                                            }

                                            if ($Origin == 'Generated' && $res->ProductGroupDetails) {
                                                if ($Node == 99) {
                                                    $subCategoryID = $mainCategoryID;
                                                }
                                                $categoryCode = $res->Code->Value;
                                                $categoryName = $res->ProductGroupDetails->Description->Value;

                                                $checkCategoryExist = $this->checkCategoryExist($categoryName, $subCategoryID, $categoryCode, $context);

                                                $categoryData = array();

                                                if (isset($checkCategoryExist)) {
                                                    $categoryData['id'] = $checkCategoryExist;
                                                } else {
                                                    $categoryData['id'] = Uuid::randomHex();
                                                }

                                                $categoryData['code'] = $res->Code->Value;
                                                $categoryData['parentId'] = $subCategoryID;
                                                if($res->ProductGroupDetails->CompositeLastModificationDateTime){
                                                    $categoryData['CompositeLastModificationDateTime'] = $res->ProductGroupDetails->CompositeLastModificationDateTime->Value;
                                                }
                                                else{
                                                    $categoryData['CompositeLastModificationDateTime'] = null;
                                                }
                                                $CommercialDescription = $res->ProductGroupDetails->CommercialDescription->Value;
                                                $categoryData['translations'] = $this->setCategoryTranslation($categoryName, $CommercialDescription, $context);

                                                //check image exist or not in shopware if not exist so add
                                                if ($res->ProductGroupDetails->Image) {
                                                    $catImgUrl = self::domain . $res->ProductGroupDetails->Image->Value;
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
                                                //last modification date check
                                                //skip
                                                $checkUpdatedProduct = $this->checkPimCategory($categoryData);
                                                if (empty($checkUpdatedProduct) || $checkUpdatedProduct == null) {
                                                    //delete
                                                    $this->removeProductFromCategory($categoryData, $context);
                                                    //insert
                                                    $categoryID = $this->categoryInsert($SequenceNo, $cat_Array, $categoryData, $cron, $context);
                                                } else {
                                                    $categoryID = $checkUpdatedProduct->getcategoryId();
                                                }
                                                $cat_Array[$SequenceNo][] = $categoryID;
                                                $ChildNodes = $res->ChildNodes;
                                                if ($ChildNodes) {
                                                    $this->insertProductGroupRecursive($cat_Array, $categoryID, $ChildNodes, $cron, $context);
                                                }


                                                /* } else {
                                                     $ChildNodes = $res->ChildNodes;
                                                     if ($ChildNodes) {
                                                         $this->insertProductGroupRecursive($cat_Array,$checkCategoryExist, $ChildNodes, $context);
                                                     }
                                                 }*/
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
                                                $MCategoryData['code']  = $res->Code->Value;
                                                $MCategoryData['parentId'] = $subCategoryID;

                                                if(array_key_exists("CompositeLastModificationDateTime",$res) ){
                                                    $MCategoryData['CompositeLastModificationDateTime'] = $res->CompositeLastModificationDateTime->Value;
                                                }
                                                else{
                                                    $MCategoryData['CompositeLastModificationDateTime'] = null;
                                                }

                                                $MCommercialDescription = $res->FullDescription->Value;


                                                $MCategoryData['translations'] = $this->setCategoryTranslation($MCategoryName, $MCommercialDescription, $context);
                                                //check image exist or not in shopware if not exist so add

                                                if ($res->Image) {
                                                    $MCatImgUrl = self::domain . $res->Image->Value;
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
                                                                'productId'=>$MProductId,
                                                                'position'=>$MSequenceNo
                                                            );
                                                        }
                                                    }
                                                    $MCategoryData['products'] = $MProductCodes;
                                                    $MCategoryData['kplngiPositions'] = $MKProductCodes;
                                                }

                                                $checkUpdatedProduct = $this->checkPimCategory($MCategoryData);
                                                if (empty($checkUpdatedProduct) || $checkUpdatedProduct == null) {
                                                    $this->removeProductFromCategory($MCategoryData, $context);
                                                    $MCategoryID = $this->categoryInsert($SequenceNo, $cat_Array, $MCategoryData, $cron, $context);
                                                } else {
                                                    $MCategoryID = $checkUpdatedProduct->getcategoryId();
                                                }
                                                $cat_Array[$SequenceNo][] = $MCategoryID;

                                                $MChildNodes = $res->ChildNodes;
                                                if ($MChildNodes) {
                                                    $this->insertProductGroupRecursive($cat_Array, $MCategoryID, $MChildNodes, $cron, $context);
                                                }
                                                /*} else {
                                                    $MChildNodes = $res->ChildNodes;
                                                    if ($MChildNodes) {
                                                        $this->insertProductGroupRecursive($cat_Array,$MCheckCategoryExist, $MChildNodes, $context);
                                                    }
                                                }*/
                                            }

                                            $counter++;
                                            $this->systemConfigService->set('PimImport.config.CategoryCounter', $counter);
                                            $this->systemConfigService->set('PimImport.config.SequenceManage', $cat_Array);
                                            $this->systemConfigService->set('PimImport.config.CategoryPublicationCode', $PublicationCode);
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
        return new JsonResponse([
            'type' => 'error',
            'message' => 'Please select sales channel',
        ]);
    }

    public function insertProductGroupRecursive($cat_Array, $subCategoryID, $ChildNodes, $cron, $context)
    {
        foreach ($ChildNodes as $res) {
            $Origin = $res->Origin->Value;

            if (isset($res->ProductGroupDetails->Level->Value)) {
                $SequenceNo = $res->ProductGroupDetails->Level->Value;
            } else {
                $SequenceNo = '0';
            }

            if ($Origin == 'Generated' && $res->ProductGroupDetails) {
                $categoryCode = $res->Code->Value;
                $categoryName = $res->ProductGroupDetails->Description->Value;

                $checkCategoryExist = $this->checkCategoryExist($categoryName, $subCategoryID, $categoryCode, $context);

                $categoryData = array();

                if (isset($checkCategoryExist)) {
                    $categoryData['id'] = $checkCategoryExist;
                } else {
                    $categoryData['id'] = Uuid::randomHex();
                }
                $categoryData['code'] = $res->Code->Value;
                $categoryData['parentId'] = $subCategoryID;
                if($res->ProductGroupDetails->CompositeLastModificationDateTime){
                    $categoryData['CompositeLastModificationDateTime'] = $res->ProductGroupDetails->CompositeLastModificationDateTime->Value;
                }
                else{
                    $categoryData['CompositeLastModificationDateTime'] = null;
                }
                $CommercialDescription = $res->ProductGroupDetails->CommercialDescription->Value;

                $categoryData['translations'] = $this->setCategoryTranslation($categoryName, $CommercialDescription, $context);

                //check image exist or not in shopware if not exist so add
                if ($res->ProductGroupDetails->Image) {
                    $catImgUrl = self::domain . $res->ProductGroupDetails->Image->Value;
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

                $checkUpdatedProduct = $this->checkPimCategory($categoryData);
                if (empty($checkUpdatedProduct) || $checkUpdatedProduct == null) {
                    $this->removeProductFromCategory($categoryData, $context);
                    $categoryID = $this->categoryInsert($SequenceNo, $cat_Array, $categoryData, $cron, $context);
                } else {
                    $categoryID = $checkUpdatedProduct->getcategoryId();
                }

                $cat_Array[$SequenceNo][] = $categoryID;
                $ChildNodes = $res->ChildNodes;
                if ($ChildNodes) {
                    $this->insertProductGroupRecursive($cat_Array, $categoryID, $ChildNodes, $cron, $context);
                }


                /*}else{
                    $ChildNodes = $res->ChildNodes;
                    if($ChildNodes) {
                        $this->insertProductGroupRecursive($cat_Array,$checkCategoryExist, $ChildNodes, $context);
                    }
                }*/
            }

            if ($Origin == 'Manual' && $res->Description) {
                $MCategoryCode = $res->Code->Value;
                $MCategoryName = $res->Description->Value;
                $MCheckCategoryExist = $this->checkCategoryExist($MCategoryName, $subCategoryID, $MCategoryCode, $context);

                $MCategoryData = array();

                if ($MCheckCategoryExist) {
                    $MCategoryData['id'] = $MCheckCategoryExist;
                } else {
                    $MCategoryData['id'] = Uuid::randomHex();
                }

                $MCategoryData['code']  = $res->Code->Value;
                $MCategoryData['parentId'] = $subCategoryID;
                if(array_key_exists("CompositeLastModificationDateTime",$res) ){
                    $MCategoryData['CompositeLastModificationDateTime'] = $res->CompositeLastModificationDateTime->Value;
                }
                else{
                    $MCategoryData['CompositeLastModificationDateTime'] = null;
                }

                $MCommercialDescription = $res->FullDescription->Value;

                $MCategoryData['translations'] = $this->setCategoryTranslation($MCategoryName, $MCommercialDescription, $context);

                //check image exist or not in shopware if not exist so add
                if ($res->Image) {
                    $MCatImgUrl = self::domain . $res->Image->Value;
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
                        $MProductId =  $this->getProductID($MProductData->ProductCode->Value);
                        $MSequenceNo = $MProductData->SequenceNo->Value;
                        if ($MProductId) {
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

                $checkUpdatedProduct = $this->checkPimCategory($MCategoryData);
                if (empty($checkUpdatedProduct) || $checkUpdatedProduct == null) {
                    $this->removeProductFromCategory($MCategoryData, $context);
                    $MCategoryID = $this->categoryInsert($SequenceNo, $cat_Array, $MCategoryData, $cron, $context);
                } else {
                    $MCategoryID = $checkUpdatedProduct->getcategoryId();
                }
                $cat_Array[$SequenceNo][] = $MCategoryID;

                $MChildNodes = $res->ChildNodes;
                if ($MChildNodes) {
                    $this->insertProductGroupRecursive($cat_Array, $MCategoryID, $MChildNodes, $cron, $context);
                }
                /*}else{
                    $MChildNodes = $res->ChildNodes;
                    if($MChildNodes) {
                        $this->insertProductGroupRecursive($cat_Array,$MCheckCategoryExist, $MChildNodes, $context);
                    }
                }*/
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
    public function categoryInsert($SequenceNo, $cat_Array, array $categoryData, $cron, Context $context): string
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
        if (isset($cat_Array) && isset($cat_Array[$SequenceNo])) {
            if (end($cat_Array[$SequenceNo])) {
                $data['afterCategoryId'] = end($cat_Array[$SequenceNo]);
            }
        } else {
            $data['afterCategoryId'] = null;
        }

        if (isset($categoryData['mediaId']) && $categoryData['mediaId']) {
            $data['mediaId'] = $categoryData['mediaId'];
        }

        if ($cron == 1) {
            //file_put_contents("PimImportLog.txt",date("l jS \of F Y h:i:s A")."> ".$categoryData['id']." Cron Category Import\n",FILE_APPEND);
        }

        $this->categoryRepository->upsert([$data], $context);

        //add product id in category
        if (isset($categoryData['products']) && $categoryID) {
            foreach ($categoryData['products'] as $productId) {
                $this->setCategoryId($productId, $categoryID, $context);
            }
        }

        //add position in kpi
        if (isset($categoryData['kplngiPositions']) && $categoryID) {
            foreach ($categoryData['kplngiPositions'] as $product) {
                $this->setkplngiPosition($product['productId'], $categoryID, $product['position'], $context);
            }
        }

        $PimCategoryID = $this->checkPimCategoryID($categoryData);

        $pimcategoryDatas = [
            'id' => !empty($PimCategoryID) ? $PimCategoryID->getId() : Uuid::randomHex(),
            'categoryId' => $categoryData['id'],
            'categoryCode'=>$categoryData['code'],
            'salesChannelId' => $_GET['salesChannelId'],
            'lastUsageAt' => $categoryData['CompositeLastModificationDateTime'],
        ];
        $this->pimCategoryRepository->upsert([$pimcategoryDatas], $context);
        return $categoryID;
    }

    //update category id in product
    public function setCategoryId($productId, $categoryId, $context)
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
            $context
        );
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

        $checkProductCategoryPositionExist = $this->checkproductCategoryPositionExist($productId, $categoryId, $context);
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
    public function checkproductCategoryPositionExist(string $productId, string $categoryId, Context $context): ?string
    {
        //set up the criteria for the search
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('categoryId', $categoryId));
        $criteria->addFilter(new EqualsFilter('productId', $productId));
        return $this->productCategoryPositionRepository->searchIds($criteria, $context)->firstId();
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

    //get category Name
    public function getCategoryName(string $categoryId, Context $context)
    {
        //set up the criteria for the search
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $categoryId));

        //search the category repository, based on the criteria
        return $this->categoryRepository->search($criteria, $context)->first()->getName();
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
    public function checkKPCategoryExist(string $categoryId, Context $context): ?string
    {
        //set up the criteria for the search
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('categoryId', $categoryId));
        return $this->kplngi_orderactive->searchIds($criteria, $context)->firstId();
    }

    //check category
    public function checkCategoryExist(object $categoryName, string $parentId, $categoryCode, Context $context): ?string
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
        $id = $this->categoryRepository->searchIds($criteria, $context)->firstId();
        return $id;
    }

    //find Layout assignment name recursive
    public function findCMSName(string $cmsPageName, Context $context): ?string
    {
        //set up the criteria for the search
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $cmsPageName));
        $cmsPageID = $this->cmsPageRepository->searchIds($criteria, $context)->firstId();
        if ($cmsPageID) {
            return $cmsPageID;
        } else {
            return $this->findCMSName('Default category layout', $context);
        }
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
                $languageData[$data->getId()]['description'] = $CommercialDescription->$languageKey;
            }
            if ($data->getName() == 'Dutch' || $data->getName() == 'Nederlands') {
                $languageKey = 'nl';
                $languageData[$data->getId()]['name'] = $categoryName->$languageKey;
                $languageData[$data->getId()]['description'] = $CommercialDescription->$languageKey;
            }
            if ($data->getName() == 'Deutsch') {
                $languageKey = 'de';
                $languageData[$data->getId()]['name'] = $categoryName->$languageKey;
                $languageData[$data->getId()]['description'] = $CommercialDescription->$languageKey;
            }
            if ($data->getName() == 'Magyar') {
                $languageKey = 'hu';
                $languageData[$data->getId()]['name'] = $categoryName->$languageKey;
                $languageData[$data->getId()]['description'] = $CommercialDescription->$languageKey;
            }
            if ($data->getName() == 'Italiano') {
                $languageKey = 'it';
                $languageData[$data->getId()]['name'] = $categoryName->$languageKey;
                $languageData[$data->getId()]['description'] = $CommercialDescription->$languageKey;
            }
            if ($data->getName() == 'Croatian') {
                $languageKey = 'hr';
                $languageData[$data->getId()]['name'] = $categoryName->$languageKey;
                $languageData[$data->getId()]['description'] = $CommercialDescription->$languageKey;
            }
            if ($data->getName() == 'Polski') {
                $languageKey = 'pl';
                $languageData[$data->getId()]['name'] = $categoryName->$languageKey;
                $languageData[$data->getId()]['description'] = $CommercialDescription->$languageKey;
            }
            if ($data->getName() == 'Română') {
                $languageKey = 'ro';
                $languageData[$data->getId()]['name'] = $categoryName->$languageKey;
                $languageData[$data->getId()]['description'] = $CommercialDescription->$languageKey;
            }
            if ($data->getName() == 'Slovenian') {
                $languageKey = 'sl';
                $languageData[$data->getId()]['name'] = $categoryName->$languageKey;
                $languageData[$data->getId()]['description'] = $CommercialDescription->$languageKey;
            }
            if ($data->getName() == 'Español') {
                $languageKey = 'es';
                $languageData[$data->getId()]['name'] = $categoryName->$languageKey;
                $languageData[$data->getId()]['description'] = $CommercialDescription->$languageKey;
            }
            if ($data->getName() == 'Čeština') {
                $languageKey = 'cs';
                $languageData[$data->getId()]['name'] = $categoryName->$languageKey;
                $languageData[$data->getId()]['description'] = $CommercialDescription->$languageKey;
            }
        }
        return $languageData;
    }

    //add image
    public function addImageToMediaFromURL(string $imageUrl, Context $context): ?string
    {
        $mediaId = null;

        //process with the cache disabled
        /*$context->disableCache(function (Context $context) use ($imageUrl, &$mediaId): void {*/

        //parse the URL
        $filePathParts = explode('/', $imageUrl);
        $fileNameParts = explode('.', array_pop($filePathParts));

        //get the file name and extension
        $fileName = $fileNameParts[0];
        //need to find the rahasya
        $fileName = str_replace('%20', '_', $fileName);
        $fileExtension = $fileNameParts[1];

        if ($fileName && $fileExtension) {
            //copy the file from the URL to the newly created local temporary file
            $filePath = tempnam(sys_get_temp_dir(), $fileName);
            file_put_contents($filePath, @file_get_contents($imageUrl));

            //create media record from the image
            $mediaId = $this->createMediaFromFile($filePath, $fileName, $fileExtension, $context);
        }
        /*});*/

        return $mediaId;
    }

    //create media
    private function createMediaFromFile(string $filePath, string $fileName, string $fileExtension, Context $context): ?string
    {
        $mediaId = null;

        //get additional info on the file
        $fileSize = filesize($filePath);
        $mimeType = mime_content_type($filePath);

        //create and save new media file to the Shopware's media library
        $mediaFile = new MediaFile($filePath, $mimeType, $fileExtension, $fileSize);
        try {
            $mediaId = $this->mediaService->createMediaInFolder('Category Media', $context, false);
            $this->fileSaver->persistFileToMedia($mediaFile, $fileName, $mediaId, $context);
        } catch (DuplicatedMediaFileNameException | Exception $e) {
            $mediaId = $this->mediaCleanup($mediaId, $context);
            if (!empty($mediaId)) {
                $this->fileSaver->persistFileToMedia($mediaFile, $fileName, $mediaId, $context);
            }
        }

        //find media in shopware media
        if (empty($mediaId)) {
            $mediaId = $this->checkImageExist($fileName, $mimeType, $context);
            try {
                $this->fileSaver->persistFileToMedia($mediaFile, $fileName, $mediaId, $context);
            } catch (Exception $e) {
            }
        }

        return $mediaId;
    }

    //delete media
    private function mediaCleanup($mediaId, Context $context)
    {
        if ($mediaId) {
            $this->mediaRepository->delete([['id' => $mediaId]], $context);
        }
        return null;
    }

    //check image exist in media
    private function checkImageExist(string $fileName, string $mimeType, Context $context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('fileName', $fileName));
        $criteria->addFilter(new EqualsFilter('mimeType', $mimeType));
        $media_object = $this->mediaRepository->searchIds($criteria, $context);
        return $media_object->firstId();
    }

    // Check the Product
    public function getProductID($productNumber = null)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productNumber', $productNumber));
        $productArray = $this->productRepository->searchIds($criteria, Context::createDefaultContext())->getIds();
        return $productArray[0] ?? '';
    }

    public function checkPimCategory(array $categoryData)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('categoryId', $categoryData['id']));
        $criteria->addFilter(new EqualsFilter('categoryCode', $categoryData['code']));
        $criteria->addFilter(new EqualsFilter('salesChannelId', $_GET['salesChannelId']));
        $criteria->addFilter(new EqualsFilter('lastUsageAt', $categoryData['CompositeLastModificationDateTime']));
        return $this->pimCategoryRepository->search($criteria, Context::createDefaultContext())->first();
    }

    public function checkPimCategoryID(array $categoryData)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('categoryId', $categoryData['id']));
        $criteria->addFilter(new EqualsFilter('salesChannelId', $_GET['salesChannelId']));
        return $this->pimCategoryRepository->search($criteria, Context::createDefaultContext())->first();
    }

    public function checkPimCategoryData()
    {
        $criteria = new Criteria();
        $Data = $this->pimCategoryRepository ->search($criteria, Context::createDefaultContext());

        $allCategoryID = [];
        foreach ($Data->getElements() as $file) {
            array_push($allCategoryID, $file->categoryId);
        }
        return $allCategoryID;
    }

    public function removeProductFromCategory($categoryData, Context $context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('categoryId', $categoryData['id']));
        $Datas = $this->productCategoryRepository->searchIds($criteria, $context)->getIds();

        foreach ($Datas as $Id) {
            $this->productCategoryRepository->delete([$Id], $context);
        }
        return null;
    }
}
