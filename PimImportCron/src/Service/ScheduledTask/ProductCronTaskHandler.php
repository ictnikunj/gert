<?php declare(strict_types=1);

namespace PimImportCron\Service\ScheduledTask;

use Shopware\Core\Content\Media\Exception\DuplicatedMediaFileNameException;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\File\MediaFile;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;

class ProductCronTaskHandler extends ScheduledTaskHandler
{
    protected $scheduledTaskRepository;
    private SystemConfigService $systemConfigService;
    private EntityRepositoryInterface $productsRepository;
    private EntityRepositoryInterface $languageRepository;
    private EntityRepositoryInterface $productManufacturerRepository;
    private EntityRepositoryInterface $taxRepository;
    private EntityRepositoryInterface $pimProductRepository;
    private EntityRepositoryInterface $productCrossSellingRepository;
    private EntityRepositoryInterface $mediaRepository;
    private MediaService $mediaService;
    private FileSaver $fileSaver;
    private EntityRepositoryInterface $productMediaRepository;
    private EntityRepositoryInterface $salesChannelRepository;
    private EntityRepositoryInterface $productVisibilityRepository;
    private EntityRepositoryInterface $propertyGroupOptionRepository;
    private EntityRepositoryInterface $acrisDownloadRepository;
    private $ICTMediaRedirect;
    private $mediaFolderRepository;
    private $snippetRepository;
    private $snippetSetRepository;
    private $productPropertyRepository;
    private $mediaThumbnailSize;
    private $propertyRepository;
    private $pimCategoryRepository;

    public function __construct(
        EntityRepositoryInterface $scheduledTaskRepository,
        SystemConfigService $systemConfigService,
        EntityRepositoryInterface $productsRepository,
        EntityRepositoryInterface $languageRepository,
        EntityRepositoryInterface $productManufacturerRepository,
        EntityRepositoryInterface $taxRepository,
        EntityRepositoryInterface $pimProductRepository,
        EntityRepositoryInterface $productCrossSellingRepository,
        EntityRepositoryInterface $mediaRepository,
        EntityRepositoryInterface $productMediaRepository,
        MediaService $mediaService,
        FileSaver $fileSaver,
        EntityRepositoryInterface $salesChannelRepository,
        EntityRepositoryInterface $productVisibilityRepository,
        EntityRepositoryInterface $propertyGroupOptionRepository,
        EntityRepositoryInterface $acrisDownloadRepository,
        EntityRepositoryInterface $ICTMediaRedirect,
        EntityRepositoryInterface $mediaFolderRepository,
        EntityRepositoryInterface $snippetRepository,
        EntityRepositoryInterface $snippetSetRepository,
        EntityRepositoryInterface $productPropertyRepository,
        EntityRepositoryInterface $mediaThumbnailSize,
        EntityRepositoryInterface $propertyRepository,
        EntityRepositoryInterface $pimCategoryRepository
    ) {
        $this->scheduledTaskRepository = $scheduledTaskRepository;
        $this->systemConfigService = $systemConfigService;
        $this->productsRepository = $productsRepository;
        $this->languageRepository = $languageRepository;
        $this->productManufacturerRepository = $productManufacturerRepository;
        $this->taxRepository = $taxRepository;
        $this->pimProductRepository = $pimProductRepository;
        $this->productCrossSellingRepository = $productCrossSellingRepository;
        $this->mediaRepository = $mediaRepository;
        $this->productMediaRepository = $productMediaRepository;
        $this->mediaService = $mediaService;
        $this->fileSaver = $fileSaver;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->productVisibilityRepository = $productVisibilityRepository;
        $this->propertyGroupOptionRepository = $propertyGroupOptionRepository;
        $this->acrisDownloadRepository = $acrisDownloadRepository;
        $this->ICTMediaRedirect = $ICTMediaRedirect;
        $this->mediaFolderRepository = $mediaFolderRepository;
        $this->snippetRepository = $snippetRepository;
        $this->snippetSetRepository = $snippetSetRepository;
        $this->productPropertyRepository = $productPropertyRepository;
        $this->mediaThumbnailSize = $mediaThumbnailSize;
        $this->propertyRepository = $propertyRepository;
        $this->pimCategoryRepository = $pimCategoryRepository;
    }

    public static function getHandledMessages(): iterable
    {
        return [ ProductCronTask::class ];
    }

    public function run(): void
    {
        $context = Context::createDefaultContext();
        $apiKey = $this->systemConfigService->get('PimImport.config.pimApiKey');
        $apiParameters = $this->systemConfigService->get('PimImport.config.pimParameters');
        $crossUrl = $this->systemConfigService->get('PimImport.config.pimCrossUrl');
        $getCronDataDateBase = $this->systemConfigService->get('PimImport.config.getCronDataDateBase');
        if ($getCronDataDateBase == '3Day') {
            $dataDate =  "&datesince=".date('Ymd', strtotime(' - 3 days'));
        } else {
            $dataDate = '';
        }
        echo $apiUrl = $crossUrl.$apiParameters.$apiKey.$dataDate;
        $apiProductData = $this->getCurlData($apiUrl);
        $last_counter = $apiProductData->Count ?? '5000';
        $counter = 1;

        for ($i=1; $i<=$last_counter; $i = $i+50) {
            $start_loop = $i;
            $apiURL = $crossUrl.'/'.$start_loop.'/'.'50'.$apiParameters.$apiKey.$dataDate;
            //$apiURL = 'https://fluidmaster.compano.com/api/jsonfeed/FM_Web_PRODUCT/1/100?filter=Code=8050452769&apikey=8A0F889905CF2C37A092B9A0C80DAEFC38E7408D188BB02A6ECF8BE6A329A690';
            $response = $this->getCurlData($apiURL);
            if (isset($response->Products)) {
                file_put_contents("ProductImportLog.txt", date("l jS \of F Y h:i:s A")."> Start Cron Main Product Import\n", FILE_APPEND);
                foreach ($response->Products as $key => $res) {
                    if ($res->ItemCount->Value > 0) {
                        $checkdate = isset($res->LastModificationDateTime) ? $res->LastModificationDateTime->Value : '';
                        $date = date('Y-m-d H:i:s', strtotime($checkdate));
                        $productNumber = isset($res->Code) ? $res->Code->Value : '';
                        //check the product
                        $checkUpdatedProduct = $this->checkUpdatedProduct($date, $productNumber);
                        dump($productNumber);
                        if (empty($checkUpdatedProduct)) {
                            if ($res->AllAttachments) {
                                $mediaIds = [];
                                $pdfIds = [];

                                $p=0;
                                $imageFlag = true;

                                foreach ($res->AllAttachments as $image) {
                                    //for pdf
                                    if (($image->AttachmentTypeType->Value == 'SCH' || $image->AttachmentTypeType->Value == 'MAN' || $image->AttachmentTypeType->Value == 'CAD' || $image->AttachmentTypeType->Value=='OTD') && $image->ParentEntityName->Value== 'PRD.Product') {
                                        $pdfPath = $image->Path->Value;
                                        $pdf_url = "https://fluidmaster.compano.com/".$pdfPath;
                                        $productCode = $res->Code->Value;
                                        $translations = $image->AttachmentTypeDescription->Value;
                                        $translations = $this->checkSCHtranslation($translations, $productCode, $context);
                                        $folderName = $image->AttachmentTypeDescription->Value;
                                        $languagecode = $image->LanguageCode->Value;
                                        $pdfId = $this->addImageToMediaFromURL($pdf_url, $folderName, $context);
                                        if ($pdfId) {
                                            $pdfIds[$p]['mediaid'] = $pdfId;
                                            $pdfIds[$p]['path'] = $pdfPath;
                                            $pdfIds[$p]['translations'] = $translations;
                                            $pdfIds[$p]['languagecode'] = $languagecode;
                                        }
                                    }

                                    //for image
                                    if ($image->AttachmentTypeType->Value == 'PPI' && $image->ParentEntityName->Value== 'PRD.Product') {
                                        $imgSeqNo = $image->SequenceNo->Value;
                                        $image_url = "https://fluidmaster.compano.com/".$image->Path->Value;
                                        $folderName = $image->AttachmentTypeType->ValueDescription;
                                        $mediaId = $this->addImageToMediaFromURL($image_url, $folderName, $context);

                                        if ($mediaId) {
                                            $mediaIds[$p]['mediaId'] = $mediaId;
                                            $mediaIds[$p]['position'] = $imgSeqNo;
                                        }
                                        $imageFlag = false;
                                    }
                                    //for Product Group Image
                                    if ($image->ParentEntityName->Value== 'PRD.ProductGroup') {
//                                        $imageFlag = true;
                                    }
                                    $p++;
                                }

                                if ($imageFlag == true) {
                                    if ($res->Image != null) {
                                        $image_url = 'https://fluidmaster.compano.com' . $res->Image->Value;
                                        $folderName = (object)[
                                            "en" => "ProductGroup",
                                            "nl" => "ProductGroup",
                                            "de" => "ProductGroup",
                                            "hu" => "ProductGroup",
                                            "it" => "ProductGroup",
                                            "hr" => "ProductGroup",
                                            "pl" => "ProductGroup",
                                            "ro" => "ProductGroup",
                                            "sl" => "ProductGroup",
                                            "es" => "ProductGroup",
                                            "cs" => "ProductGroup",
                                        ];
                                        $mediaId = $this->addImageToMediaFromURL($image_url, $folderName, $context);
                                        if ($mediaId) {
                                            $mediaIds[]=array(
                                                'mediaId'   =>  $mediaId,
                                                'position'  =>  1
                                            );
                                        }
                                    }
                                }
                            }
                            $productData = array();

                            //check product already exist or not
                            $productId = $this->getProductID($productNumber);
                            if (!$productId) {
                                //create new product id
                                $productId = Uuid::randomHex();
                            }

                            $productData['id'] = $productId;

                            $productData['title'] = isset($res->Description) ? $res->Description->Value : '';

                            $productData['productNumber'] = isset($res->Code) ? $res->Code->Value : '';
                            $productData['ModificationCode'] = isset($res->ModificationCode->Value) ? $res->ModificationCode->Value : '';

                            $productData['description'] = isset($res->TechnicalDescription) ? $res->TechnicalDescription->Value : '';
                            $productData['shortDescription'] = isset($res->CommercialDescription) ? $res->CommercialDescription->Value : '';

                            $productData['price'] = isset($res->Items[0]->GrossPriceInfoPrice) ? $res->Items[0]->GrossPriceInfoPrice->Value : '0';
                            $productData['manufacturer'] = isset($res->Items[0]->ItemSetCode) ? $res->Items[0]->ItemSetCode->Value : '';
                            $productData['ean'] = isset($res->Items[0]->EAN) ? $res->Items[0]->EAN->Value : '';

                            $productData['updatedDate'] = isset($res->LastModificationDateTime) ? $res->LastModificationDateTime->Value : '';
                            $productData['media'] = isset($mediaIds) ? $mediaIds : '';
                            $productData['pdfsch'] = isset($pdfIds) ? $pdfIds : '';
                            $existingProductId = $this->getProductID($productNumber);

                            if ($existingProductId) {
                                $this->removeProductProperty($existingProductId, $context);
                            }
                            //insert step by step find in main properties side
                            if (isset($res->UDF_PRD_ProductWarranty->ValueDescription)) {
                                $propId1 = $this->getPropertyOptionId($res->UDF_PRD_ProductWarranty->ValueDescription, $context, 'UDF_PRD_ProductWarranty');
                                if ($propId1 !== null) {
                                    $productData['properties'][] = $propId1;
                                }
                            }

                            if (isset($res->UDF_DoP->ValueDescription)) {
                                $propId2 = $this->getPropertyOptionId($res->UDF_DoP->ValueDescription, $context, 'UDF_DoP');
                                if ($propId2 !== null) {
                                    $productData['properties'][] = $propId2;
                                }
                            }

                            if (isset($res->UDF_Reservoir_Types->ValueDescription)) {
                                $propId3 = $this->getPropertyOptionId($res->UDF_Reservoir_Types->ValueDescription, $context, 'UDF_Reservoir_Types');
                                if ($propId3 !== null) {
                                    $productData['properties'][] = $propId3;
                                }
                            }

                            if (isset($res->UDF_Series[0]->ValueDescription)) {
                                $propId4 = $this->getPropertyOptionId($res->UDF_Series[0]->ValueDescription, $context, 'UDF_Series');
                                if ($propId4 !== null) {
                                    $productData['properties'][] = $propId4;
                                }
                            }

                            if (isset($res->UDF_PRD_ProductBackorderWarranty->ValueDescription)) {
                                $propId5 = $this->getPropertyOptionId($res->UDF_PRD_ProductBackorderWarranty->ValueDescription, $context, 'UDF_PRD_ProductBackorderWarranty');
                                if ($propId5 !== null) {
                                    $productData['properties'][] = $propId5;
                                }
                            }
                            if (isset($res->UDF_PRD_AccessibleProducts->ValueDescription)) {
                                $propId6 = $this->getPropertyOptionId($res->UDF_PRD_AccessibleProducts->ValueDescription, $context, 'UDF_PRD_AccessibleProducts');
                                if ($propId6 !== null) {
                                    $productData['properties'][] = $propId6;
                                }
                            }

                            if ($res->Items) {
                                //sales channel add in assignment > sales channels
                                $salesChannelArray = [];
                                foreach ($res->Items as $item) {
                                    if ($item->ItemSetCode->Value) {
                                        $salesChannelId = $this->getSalesChannelId($item->ItemSetCode->Value, $context);
                                        if ($productId && $salesChannelId) {
                                            $salesChannelObject = $this->checkSalesChannelExist($productId, $salesChannelId, $context);
                                            if (!$salesChannelObject) {
                                                $salesChannelArray[] = array(
                                                    'productId' => $productId,
                                                    'salesChannelId' => $salesChannelId,
                                                    'visibility' => 30
                                                );
                                            }
                                        } elseif ($salesChannelId) {
                                            $salesChannelArray[] = array(
                                                'productId' => $productId,
                                                'salesChannelId' => $salesChannelId,
                                                'visibility' => 30
                                            );
                                        }
                                    }
                                }
                                if ($salesChannelArray) {
                                    $salesChannelArray = array_map("unserialize", array_unique(array_map("serialize", $salesChannelArray)));
                                }
                                $productData['visibilities'] = $salesChannelArray;
                            }
                            $this->productInsert($productData, $context);
                        }
                    }

                    $counter++;
                }
                file_put_contents("ProductImportLog.txt", date("l jS \of F Y h:i:s A")."> End Main Product Import\n", FILE_APPEND);
            }
        }
    }

    public function getCurlData($apiUrl)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            echo 'Error Curl Time Out Product Import';
            file_put_contents("ProductImportLog.txt", date("l jS \of F Y h:i:s A")."> Error Curl Time Out Product Import\n", FILE_APPEND);
            return new JsonResponse(['type'=>'error','message' =>  $err]);
        } else {
            return json_decode($response);
        }
    }

    public function removeProductProperty($productId, $context): ?string
    {
        $propertycriteria = new Criteria();
        $propertycriteria->addFilter(
            new NotFilter(
                NotFilter::CONNECTION_AND,
                [
                    new EqualsFilter('customFields', null),
                ]
            )
        );
        $propertycriteria->addAssociation('options');
        $properties = $this->propertyRepository->search($propertycriteria, $context)->getElements();
        foreach ($properties as $propertie) {
            if ($propertie->getoptions()) {
                foreach ($propertie->getoptions()->getelements() as $propertiesOptions) {
                    $productPropertyCriteria = new Criteria();
                    $productPropertyCriteria->addFilter(new EqualsFilter('productId', $productId));
                    $productPropertyCriteria->addFilter(new EqualsFilter('optionId', $propertiesOptions->getId()));
                    $findID = $this->productPropertyRepository->searchIds($productPropertyCriteria, $context);
                    if ($findID->getTotal() != 0) {
                        $this->productPropertyRepository->delete([
                            [
                                'productId' => $productId,
                                'optionId' => $propertiesOptions->getId()
                            ]
                        ], $context);
                    }
                }
            }
        }
        return $productId;
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

    //get Property Option id
    public function getPropertyOptionId($propertyOption, $context, $cName)
    {
        $languageKey = $this->getDefaultLanguageKey($context);
        if ($languageKey == 'en') {
            if ($propertyOption->en == 'Yes') {
                $propertyOption->en = 'true';
            }
            if ($propertyOption->en == 'No') {
                $propertyOption->en = 'false';
            }
        }
        if ($languageKey == 'nl') {
            if ($propertyOption->nl == 'Ja') {
                $propertyOption->nl = 'true';
            }
            if ($propertyOption->nl == 'Nee') {
                $propertyOption->nl = 'false';
            }
        }

        $criteria = new Criteria();
        $criteria->addAssociation('group');
        $criteria->addFilter(new EqualsFilter('name', $propertyOption->$languageKey));
        $criteria->addFilter(new EqualsFilter('group.customFields.custom_property_api_name_field', $cName));
        $propertyGroupOption = $this->propertyGroupOptionRepository->search($criteria, $context)->getElements();

        if ($propertyGroupOption) {
            return array(
                'id' => array_values($propertyGroupOption)[0]->getid(),
                'property_group_id' => array_values($propertyGroupOption)[0]->getgroupId()
            );
        } else {
            return null;
        }
    }

    //get sales channel id
    public function getSalesChannelId($itemSetCode, $context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customFields.custom_pim_sales_channel_name', $itemSetCode));
        $salesChannelObject = $this->salesChannelRepository->searchIds($criteria, $context)->getIds();
        return $salesChannelObject[0] ?? '';
    }

    // Check Updated Product
    public function checkUpdatedProduct($date = null, $productNumber = null)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('lastUsageAt', $date));
        $criteria->addFilter(new EqualsFilter('productNumber', $productNumber));
        return $this->pimProductRepository->searchIds($criteria, Context::createDefaultContext())->firstId();
    }

    public function getPimCatId($catId, $context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('categoryId', $catId));
        return $this->pimCategoryRepository->search($criteria, $context)->first();
    }

    // Insert Products
    public function productInsert(array $productData, Context $context)
    {
        //check the product
        $productID = $this->checkProductID($productData['productNumber']);

        //last usage null
        $getAllCatIds = $productID->getcategoryIds();
        if ($getAllCatIds) {
            foreach ($getAllCatIds as $catId) {
                $categoryObject = $this->getPimCatId($catId, $context);
                if ($categoryObject) {
                    $pimCatId = $categoryObject->getId();
                    if ($pimCatId) {
                        $pimCatData = [
                            'id' => $pimCatId,
                            'lastUsageAt' => null
                        ];
                        dump($pimCatData);
                        $this->pimCategoryRepository->upsert([$pimCatData], $context);
                    }
                }
            }
        }
        $productId = $productData['id'];

        //check the manufacturer
        $manufacturer = $this->checkManufacturer($productData['manufacturer'], $context);
        if (empty($manufacturer->getIds())) {
            $manufacturerData = array('name' => $productData['manufacturer']);
        } else {
            $manufacturerData = array('id' => $manufacturer->getIds()[0]);
        }

        //check the language
        $languageData = $this->checkLanguage($productData, $context);

        // Product Added or Updated
        $media_array = [];
        $k=0;
        $prID = !empty($productID) ? $productID->getId() : $productId;
        if ($productData['media']) {
            // $productId = $productData['id'];
            // $DuplicatedimageRemove = array_map("unserialize", array_unique(array_map("serialize", $productData['media'])));
            //delete all product image from product
            $this->removeProductMedia($prID, $context);
            foreach ($productData['media'] as $mediadata) {
                if ($mediadata['mediaId']) {
                    $media_array[$k]['id'] = Uuid::randomHex();
                    $media_array[$k]['mediaId'] = $mediadata['mediaId'];
                    $media_array[$k]['position'] = $mediadata['position'] ?? '999';
                    $k++;
                }
            }
        }

        $media_array = array_map("unserialize", array_unique(array_map("serialize", $media_array)));

        $pdf_array = [];
        $l = 0;

        $this->removeProductPDF($prID, $context);

        if ($productData['pdfsch']) {
            foreach ($productData['pdfsch'] as $pdfdata) {
                if ($pdfdata['mediaid']) {
                    $checkPDFExist = $this->checkProductPDFExist($prID, $pdfdata['mediaid'], $context);
                    if ($checkPDFExist) {
                        $pdf_array[$l]['id'] = $checkPDFExist;
                    } else {
                        $pdf_array[$l]['id'] = Uuid::randomHex();
                    }
                    $pdf_array[$l]['mediaId'] = $pdfdata['mediaid'];
                    $pdf_array[$l]['position'] = $l + 1;
                    $pdf_array[$l]['translations'] = $pdfdata['translations'];
                    if ($pdfdata['languagecode'] !== "") {
                        $languageids = $this->findLanguageID($pdfdata['languagecode']);
                        $pdf_array[$l]['languages'] = [['id'=>$languageids]];
                    }
                    $l++;
                }
                //for media redirect
                if ($pdfdata['mediaid'] && $pdfdata['path']) {
                    $checkMediaRedirectExist = $this->checkMediaRedirectExist($pdfdata['mediaid'], $pdfdata['path'], $context);
                    if ($checkMediaRedirectExist) {
                        $MediaRedirectId = $checkMediaRedirectExist;
                    } else {
                        $MediaRedirectId = Uuid::randomHex();
                    }
                    $MediaRedirectData = [
                        'id' => $MediaRedirectId,
                        'url' => $pdfdata['path'],
                        'mediaId' => $pdfdata['mediaid']
                    ];
                    $this->ICTMediaRedirect->upsert([$MediaRedirectData], $context);
                }
            }
        }
        usort($media_array, function ($x, $y) {
            return $x['position'] <=> $y['position'];
        });

        // For product disabled in Shopware
        if ($productData['ModificationCode'] == "Deleted" || $productData['ModificationCode'] == "Archived") {
            $productData['ModificationCode'] = false;
        } else {
            $productData['ModificationCode'] = true;
        }

        $data = [
            'id' => !empty($productID) ? $productID->getId() : $productId,
            'productNumber' => $productData['productNumber'],
            'stock' => !empty($productID) ? $productID->getStock() : 0,
            'price' => [
                ['currencyId' => Defaults::CURRENCY, 'gross' => $productData['price'] ? round($productData['price']*1.21, 2) : 0, 'net' => $productData['price'] ? $productData['price'] : 0, 'linked' => true],
            ],
            'ean' => $productData['ean'],
            'taxId' => $this->getTaxId($context),
            'translations' => $languageData,
            'media' => $media_array,
            'acrisDownloads'=>$pdf_array,
            'visibilities' => $productData['visibilities'],
            'active' => $productData['ModificationCode'],
        ];

        if (isset($productData['properties']) && $productData['properties']) {
            $data['properties'] = $productData['properties'];
        }

        //first image auto set to cover
        if (isset($media_array[0]['id']) && $media_array[0]['id']) {
            $data['coverId'] = $media_array[0]['id'];
        }
        if (isset($manufacturerData['name']) && $manufacturerData['name']) {
            $data['manufacturer'] = $manufacturerData;
        }

        //insert in product
        $productDatas = $this->productsRepository->upsert([$data], $context);

        $pId = !empty($productID) ? $productID->getId() : $productId;

        // Product Cross Selling Related Items
        $firstProductCrossSelling = 'Related Items';
        $productcrossDatas = $this->checkproductCrossSelling($pId, $firstProductCrossSelling, $context);
        if (empty($productcrossDatas)) {
            $this->insertproductCrossSelling($pId, $firstProductCrossSelling, $context);
        }

        // Product Cross Selling Related Items
        $firstProductParts = 'Product Parts';
        $productcrossDatas = $this->checkproductCrossSelling($pId, $firstProductParts, $context);
        if (empty($productcrossDatas)) {
            $this->insertproductCrossSelling($pId, $firstProductParts, $context);
        }

        // Product Cross Selling Related Items
        $firstProductAddon = 'Addon Products';
        $productcrossDatas = $this->checkproductCrossSelling($pId, $firstProductAddon, $context);
        if (empty($productcrossDatas)) {
            $this->insertproductCrossSelling($pId, $firstProductAddon, $context);
        }

        //check the pimData
        $PimID = $this->checkPimID($productData['productNumber']);

        // Pip Import Data
        $pimData = [
            'id' => !empty($PimID) ? $PimID->getId() : Uuid::randomHex(),
            'productNumber' => $productData['productNumber'],
            'lastUsageAt' => $productData['updatedDate'],
        ];
        $this->pimProductRepository->upsert([$pimData], $context);
    }
    // Find Labguage ID
    public function findLanguageID($languageCode = null)
    {
        if ($languageCode == "nl") {
            $languageName = "Dutch";
        } elseif ($languageCode == "de") {
            $languageName = "Deutsch";
        } elseif ($languageCode == "hu") {
            $languageName = "Magyar";
        } elseif ($languageCode == "it") {
            $languageName = "Italiano";
        } elseif ($languageCode == "hr") {
            $languageName = "Croatian";
        } elseif ($languageCode == "pl") {
            $languageName = "Polski";
        } elseif ($languageCode == "ro") {
            $languageName = "Română";
        } elseif ($languageCode == "sl") {
            $languageName = "Slovenian";
        } elseif ($languageCode == "es") {
            $languageName = "Español";
        } elseif ($languageCode == "cs") {
            $languageName = "Čeština";
        } else {
            $languageName = "English";
        }
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $languageName));
        $languageData = $this->languageRepository->search($criteria, Context::createDefaultContext())->first();
        return $languageData->getID();
    }
    // Check the Product
    public function checkProductID($productNumber = null)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productNumber', $productNumber));
        return $this->productsRepository->search($criteria, Context::createDefaultContext())->first();
    }

    // Check the Product Cross Selling Related Items
    public function checkproductCrossSelling($productNumber = null, $name = null, Context $context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productId', $productNumber));
        $criteria->addFilter(new EqualsFilter('name', $name));
        return $this->productCrossSellingRepository->searchIds($criteria, $context)->getIds();
    }

    // Check the Pim
    public function getProductID($productNumber = null)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productNumber', $productNumber));
        $productObject = $this->productsRepository->search($criteria, Context::createDefaultContext())->first();
        if ($productObject) {
            $productId = $productObject->getid();
        } else {
            $productId = '';
        }
        return $productId;
    }

    // Check the Pim
    public function checkPimID($productNumber = null)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productNumber', $productNumber));
        return $this->pimProductRepository->search($criteria, Context::createDefaultContext())->first();
    }

    //remove Product Media

    public function removeProductMedia($prID, $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productId', $prID));
        $productMediaObjects = $this->productMediaRepository->search($criteria, $context);
        foreach ($productMediaObjects as $productMediaObject) {
            $this->productMediaRepository->delete([['id' => $productMediaObject->getID()]], $context);
        }
        return $prID;
    }

    // Check the Manufacturer
    public function checkManufacturer(string $manufacturer, Context $context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $manufacturer));
        return $this->productManufacturerRepository->searchIds($criteria, $context);
    }

    // Check the Language
    public function checkLanguage(array $productData, Context $context)
    {
        $ENDescription = $productData['description']->en;
        $ENShortDescription = $productData['shortDescription']->en;
        $languageData = array();
        $criteria = new Criteria();
        $datas = $this->languageRepository->search($criteria, $context)->getElements();
        foreach ($datas as $key => $data) {
            if ($data->getName() == 'English') {
                $languageKey = 'en';
                $languageData[$data->getId()]['name'] = $productData['title']->$languageKey ? $productData['title']->$languageKey : 'Product';
                $languageData[$data->getId()]['description'] = $productData['description']->$languageKey;
                $languageData[$data->getId()]['customFields'] = ['short_description' => $productData['shortDescription']->$languageKey];
            }
            if ($data->getName() == 'Dutch' || $data->getName() == 'Nederlands') {
                $languageKey = 'nl';
                $languageData[$data->getId()]['name'] = $productData['title']->$languageKey ? $productData['title']->$languageKey : 'Product';
                $languageData[$data->getId()]['description'] = ($ENDescription == $productData['description']->$languageKey ? '&nbsp;' : $productData['description']->$languageKey);
                $languageData[$data->getId()]['customFields'] = ['short_description' => ($ENShortDescription == $productData['shortDescription']->$languageKey ? '&nbsp;' : $productData['shortDescription']->$languageKey)];
            }
            if ($data->getName() == 'Deutsch') {
                $languageKey = 'de';
                $languageData[$data->getId()]['name'] = $productData['title']->$languageKey ? $productData['title']->$languageKey : 'Product';
                $languageData[$data->getId()]['description'] = ($ENDescription == $productData['description']->$languageKey ? '&nbsp;' : $productData['description']->$languageKey);
                $languageData[$data->getId()]['customFields'] = ['short_description' => ($ENShortDescription == $productData['shortDescription']->$languageKey ? '&nbsp;' : $productData['shortDescription']->$languageKey)];
            }
            if ($data->getName() == 'Magyar') {
                $languageKey = 'hu';
                $languageData[$data->getId()]['name'] = $productData['title']->$languageKey ? $productData['title']->$languageKey : 'Product';
                $languageData[$data->getId()]['description'] = ($ENDescription == $productData['description']->$languageKey ? '&nbsp;' : $productData['description']->$languageKey);
                $languageData[$data->getId()]['customFields'] = ['short_description' => ($ENShortDescription == $productData['shortDescription']->$languageKey ? '&nbsp;' : $productData['shortDescription']->$languageKey)];
            }
            if ($data->getName() == 'Italiano') {
                $languageKey = 'it';
                $languageData[$data->getId()]['name'] = $productData['title']->$languageKey ? $productData['title']->$languageKey : 'Product';
                $languageData[$data->getId()]['description'] = ($ENDescription == $productData['description']->$languageKey ? '&nbsp;' : $productData['description']->$languageKey);
                $languageData[$data->getId()]['customFields'] = ['short_description' => ($ENShortDescription == $productData['shortDescription']->$languageKey ? '&nbsp;' : $productData['shortDescription']->$languageKey)];
            }
            if ($data->getName() == 'Croatian') {
                $languageKey = 'hr';
                $languageData[$data->getId()]['name'] = $productData['title']->$languageKey ? $productData['title']->$languageKey : 'Product';
                $languageData[$data->getId()]['description'] = ($ENDescription == $productData['description']->$languageKey ? '&nbsp;' : $productData['description']->$languageKey);
                $languageData[$data->getId()]['customFields'] = ['short_description' => ($ENShortDescription == $productData['shortDescription']->$languageKey ? '&nbsp;' : $productData['shortDescription']->$languageKey)];
            }
            if ($data->getName() == 'Polski') {
                $languageKey = 'pl';
                $languageData[$data->getId()]['name'] = $productData['title']->$languageKey ? $productData['title']->$languageKey : 'Product';
                $languageData[$data->getId()]['description'] = ($ENDescription == $productData['description']->$languageKey ? '&nbsp;' : $productData['description']->$languageKey);
                $languageData[$data->getId()]['customFields'] = ['short_description' => ($ENShortDescription == $productData['shortDescription']->$languageKey ? '&nbsp;' : $productData['shortDescription']->$languageKey)];
            }
            if ($data->getName() == 'Română') {
                $languageKey = 'ro';
                $languageData[$data->getId()]['name'] = $productData['title']->$languageKey ? $productData['title']->$languageKey : 'Product';
                $languageData[$data->getId()]['description'] = ($ENDescription == $productData['description']->$languageKey ? '&nbsp;' : $productData['description']->$languageKey);
                $languageData[$data->getId()]['customFields'] = ['short_description' => ($ENShortDescription == $productData['shortDescription']->$languageKey ? '&nbsp;' : $productData['shortDescription']->$languageKey)];
            }
            if ($data->getName() == 'Slovenian') {
                $languageKey = 'sl';
                $languageData[$data->getId()]['name'] = $productData['title']->$languageKey ? $productData['title']->$languageKey : 'Product';
                $languageData[$data->getId()]['description'] = ($ENDescription == $productData['description']->$languageKey ? '&nbsp;' : $productData['description']->$languageKey);
                $languageData[$data->getId()]['customFields'] = ['short_description' => ($ENShortDescription == $productData['shortDescription']->$languageKey ? '&nbsp;' : $productData['shortDescription']->$languageKey)];
            }
            if ($data->getName() == 'Español') {
                $languageKey = 'es';
                $languageData[$data->getId()]['name'] = $productData['title']->$languageKey ? $productData['title']->$languageKey : 'Product';
                $languageData[$data->getId()]['description'] = ($ENDescription == $productData['description']->$languageKey ? '&nbsp;' : $productData['description']->$languageKey);
                $languageData[$data->getId()]['customFields'] = ['short_description' => ($ENShortDescription == $productData['shortDescription']->$languageKey ? '&nbsp;' : $productData['shortDescription']->$languageKey)];
            }
            if ($data->getName() == 'Čeština') {
                $languageKey = 'cs';
                $languageData[$data->getId()]['name'] = $productData['title']->$languageKey ? $productData['title']->$languageKey : 'Product';
                $languageData[$data->getId()]['description'] = ($ENDescription == $productData['description']->$languageKey ? '&nbsp;' : $productData['description']->$languageKey);
                $languageData[$data->getId()]['customFields'] = ['short_description' => ($ENShortDescription == $productData['shortDescription']->$languageKey ? '&nbsp;' : $productData['shortDescription']->$languageKey)];
            }
        }
        return $languageData;
    }

    // Check the Language
    public function checkSCHtranslation(object $translations, string $productCode, Context $context)
    {
        $languageData = array();
        $criteria = new Criteria();
        $datas = $this->languageRepository->search($criteria, $context)->getElements();
        foreach ($datas as $key => $data) {
            if ($data->getName() == 'English') {
                $languageKey = 'en';
                $languageData[$data->getId()]['title'] = $translations->$languageKey.' '.$productCode;
            }
            if ($data->getName() == 'Dutch' || $data->getName() == 'Nederlands') {
                $languageKey = 'nl';
                $languageData[$data->getId()]['title'] = $translations->$languageKey.' '.$productCode;
            }
            if ($data->getName() == 'Deutsch') {
                $languageKey = 'de';
                $languageData[$data->getId()]['title'] = $translations->$languageKey.' '.$productCode;
            }
            if ($data->getName() == 'Magyar') {
                $languageKey = 'hu';
                $languageData[$data->getId()]['title'] = $translations->$languageKey.' '.$productCode;
            }
            if ($data->getName() == 'Italiano') {
                $languageKey = 'it';
                $languageData[$data->getId()]['title'] = $translations->$languageKey.' '.$productCode;
            }
            if ($data->getName() == 'Croatian') {
                $languageKey = 'hr';
                $languageData[$data->getId()]['title'] = $translations->$languageKey.' '.$productCode;
            }
            if ($data->getName() == 'Polski') {
                $languageKey = 'pl';
                $languageData[$data->getId()]['title'] = $translations->$languageKey.' '.$productCode;
            }
            if ($data->getName() == 'Română') {
                $languageKey = 'ro';
                $languageData[$data->getId()]['title'] = $translations->$languageKey.' '.$productCode;
            }
            if ($data->getName() == 'Slovenian') {
                $languageKey = 'sl';
                $languageData[$data->getId()]['title'] = $translations->$languageKey.' '.$productCode;
            }
            if ($data->getName() == 'Español') {
                $languageKey = 'es';
                $languageData[$data->getId()]['title'] = $translations->$languageKey.' '.$productCode;
            }
            if ($data->getName() == 'Čeština') {
                $languageKey = 'cs';
                $languageData[$data->getId()]['title'] = $translations->$languageKey.' '.$productCode;
            }
        }
        return $languageData;
    }

    // Get Tax
    private function getTaxId(Context $context): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('position', 1));
        return $this->taxRepository->searchIds($criteria, $context)->firstId();
    }

    // Insert Cross selling
    public function insertproductCrossSelling($pId = null, $name = null, Context $context)
    {
        $languageData = $this->checkLanguagCrossSellinge($name, $context);

        $crossSellingData = $this->productCrossSellingRepository->upsert(
            [
                [
                    'name'              => $name,
                    'type'              => 'productList',
                    'position'          =>  1,
                    'active'            =>  true,
                    'productId'         =>  $pId,
                    'translations'      =>  $languageData,
                ]
            ],
            $context
        );
        return $crossSellingData->getEvents()->getElements()[0]->getids()[0];
    }

    // Check the Language
    public function checkLanguagCrossSellinge($name = null, Context $context)
    {
        $languageData = array();
        $criteria = new Criteria();
        $datas = $this->languageRepository->search($criteria, $context)->getElements();
        foreach ($datas as $key => $data) {
            $languageData[$data->getId()]['name'] = $name;
        }
        return $languageData;
    }

    //add image
    public function addImageToMediaFromURL(string $imageUrl, $folderName, Context $context)
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
            $filePath = tempnam(sys_get_temp_dir(), $fileName);
            @file_put_contents($filePath, @file_get_contents($imageUrl));
            //create media record from the image
            $mediaId = $this->createMediaFromFile($filePath, $fileName, $fileExtension, $folderName, $context);
        }
        return $mediaId;
    }

    //create media
    private function createMediaFromFile(string $filePath, string $fileName, string $fileExtension, $folderName, Context $context)
    {
        $mediaId = null;

        //get additional info on the file
        $fileSize = filesize($filePath);
        $mimeType = mime_content_type($filePath);

        //create and save new media file to the Shopware's media library
        $mediaFile = new MediaFile($filePath, $mimeType, $fileExtension, $fileSize);
        $languageKey = $this->getDefaultLanguageKey($context);

        try {
            $folderId = $this->createFolderInMedia($folderName->$languageKey, $context);
            $mediaId = $this->createMediaId($folderId, $fileName, $context);
            $this->fileSaver->persistFileToMedia($mediaFile, $fileName, $mediaId, $context);
        } catch (DuplicatedMediaFileNameException | \Exception $e) {
            /*echo($e->getMessage());*/
            $mediaId = $this->mediaCleanup($mediaId, $context);
            if (!empty($mediaId)) {
                $this->fileSaver->persistFileToMedia($mediaFile, $fileName, $mediaId, $context);
            }
        }
        //find media in shopware media
        if (empty($mediaId)) {
            $mediaId = $this->checkImageExist($fileName, $mimeType, $context);
            try {
                if (!empty($mediaFile) && $fileName != null && $mediaId != null) {
                    $this->fileSaver->persistFileToMedia($mediaFile, $fileName, $mediaId, $context);
                }
            } catch (DuplicatedMediaFileNameException | \Exception $e) {
            }
        }
        if ($folderName) {
            $this->createSnippet($folderName->$languageKey, $folderName, $context);
        }
        return $mediaId;
    }

    //create snippet
    private function createSnippet($mainFolderName, $folderNameArray, Context $context)
    {
        foreach ($folderNameArray as $key=>$folderName) {
            if ($key == 'en') {
            } else {
                if ($key == 'nl') {
                    $locale = 'nl-NL';
                } elseif ($key == 'de') {
                    $locale = 'de-DE';
                } elseif ($key == 'hu') {
                    $locale = 'hu-HU';
                } elseif ($key == 'it') {
                    $locale = 'it-IT';
                } elseif ($key == 'hr') {
                    $locale = 'hr-HR';
                } elseif ($key == 'pl') {
                    $locale = 'pl-PL';
                } elseif ($key == 'ro') {
                    $locale = 'ro-MD';
                } elseif ($key == 'sl') {
                    $locale = 'sl-SI';
                } elseif ($key == 'es') {
                    $locale = 'es-ES';
                } elseif ($key == 'cs') {
                    $locale = 'cs-CZ';
                }
                $setId = $this->getSnippetSetIdForLocale($locale, $context);
                if ($setId != null) {
                    $checkSnippetExist = $this->checkSnippetExist($mainFolderName, $folderName, $setId, $context);
                    if ($checkSnippetExist == "0") {
                        $checkSnippetValue = $this->checkSnippetExistValue($mainFolderName, $setId, $context);
                        if ($checkSnippetValue == null) {
                            $siddata = Uuid::randomHex();
                        } else {
                            $siddata = $checkSnippetValue;
                        }
                        $snippet = [
                            'id' => $siddata,
                            'translationKey' => $mainFolderName,
                            'value' => $folderName,
                            'setId' => $setId,
                            'author' => 'user/raj',
                        ];
                        $object = $this->snippetRepository->upsert([$snippet], $context);
                    }
                }
            }
        }
        return null;
    }

    //check snippet value
    private function checkSnippetExistValue($mainFolderName, $setId, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('translationKey', $mainFolderName));
        $criteria->addFilter(new EqualsFilter('setId', $setId));
        return $this->snippetRepository->searchIds($criteria, $context)->getIds()[0] ?? null;
    }

    //check folder in media
    private function checkSnippetExist($mainFolderName, $folderName, $setId, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('translationKey', $mainFolderName));
        $criteria->addFilter(new EqualsFilter('value', $folderName));
        $criteria->addFilter(new EqualsFilter('setId', $setId));
        $snippetObject = $this->snippetRepository->search($criteria, $context)->count();
        return "".$snippetObject;
    }

    private function getSnippetSetIdForLocale(string $locale, $context): ?string
    {
        $criteria = (new Criteria())->addFilter(new EqualsFilter('iso', $locale))->setLimit(1);
        return $this->snippetSetRepository->searchIds($criteria, $context)->getIds()[0] ?? null;
    }

    //delete media
    private function mediaCleanup($mediaId, Context $context)
    {
        if ($mediaId) {
            $this->mediaRepository->delete([['id' => $mediaId]], $context);
        }
        return null;
    }

    //check folder in media
    private function checkFolderInMedia($folderName, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $folderName));
        $mediaFolderObject = $this->mediaFolderRepository->searchIds($criteria, $context);
        return $mediaFolderObject->firstId();
    }

    //create folder in media
    private function createFolderInMedia($folderName, Context $context): ?string
    {
        $folderId = $this->checkFolderInMedia($folderName, $context);
        $criteria = new Criteria();
        $mediaThumbnailObject = $this->mediaThumbnailSize->searchIds($criteria, $context)->getData();
        $mediaThumbnailArray = array();
        foreach ($mediaThumbnailObject as $media) {
            $mediaThumbnailArray[] = $media;
        }
        if (!$folderId) {
            $folderId = Uuid::randomHex();
            $mediaId = $this->mediaFolderRepository->upsert([
                [
                    'id' => $folderId,
                    'name' => $folderName,
                    'useParentConfiguration' => false,
                    'configuration' => [
                        'id' => Uuid::randomHex(),
                        'createThumbnails' => true,
                        'keepAspectRatio' => true,
                        'thumbnailQuality' => 80,
                        'mediaThumbnailSizes'=> $mediaThumbnailArray,
                    ],
                ],
            ], $context);
        }
        return $folderId;
    }

    //create media id
    private function createMediaId($folderId, $fileName, Context $context): ?string
    {
        $mediaId = Uuid::randomHex();
        $this->mediaRepository->create(
            [
                [
                    'id' => $mediaId,
                    'private' => false,
                    'mediaFolderId' => $folderId,
                ],
            ],
            $context
        );
        return $mediaId;
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

//    private function checkProductImageExist (string $productId,string $mediaId, Context $context): ?string
//    {
//        $criteria = new Criteria();
//        $criteria->addFilter(new EqualsFilter('productId',$productId));
//        $criteria->addFilter(new EqualsFilter('mediaId',$mediaId));
//        $media_object = $this->productMediaRepository->searchIds($criteria, $context);
//        return $media_object->firstId();
//    }

    private function checkProductPDFExist(string $productId, string $mediaId, Context $context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productId', $productId));
        $criteria->addFilter(new EqualsFilter('mediaId', $mediaId));
        $media_object = $this->acrisDownloadRepository->searchIds($criteria, $context);
        return $media_object->firstId();
    }

    private function removeProductPDF(string $prID, Context $context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productId', $prID));
        $media_objects = $this->acrisDownloadRepository->searchIds($criteria, $context)->getids();
        foreach ($media_objects as $media_object) {
            $this->acrisDownloadRepository->delete([['id' => $media_object]], $context);
        }
    }

    private function checkMediaRedirectExist(string $mediaId, string $path, Context $context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('url', $path));
        $criteria->addFilter(new EqualsFilter('mediaId', $mediaId));
        $media_object = $this->ICTMediaRedirect->searchIds($criteria, $context);
        return $media_object->firstId();
    }

    //check sales channel exist or not
    private function checkSalesChannelExist(string $productId, string $salesChannelId, Context $context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productId', $productId));
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));
        $sales_object = $this->productVisibilityRepository->searchIds($criteria, $context);
        return $sales_object->firstId();
    }
}
