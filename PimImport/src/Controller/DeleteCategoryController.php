<?php declare(strict_types=1);

namespace PimImport\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 */
class DeleteCategoryController extends AbstractController
{
    private $systemConfigService;
    private $salesChannelRepository;
    private $categoryRepository;
    private $languageRepository;
    private $pimCategoryRepository;

    public function __construct(
        SystemConfigService       $systemConfigService,
        EntityRepositoryInterface $salesChannelRepository,
        EntityRepositoryInterface $categoryRepository,
        EntityRepositoryInterface $languageRepository,
        EntityRepositoryInterface $pimCategoryRepository
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->categoryRepository = $categoryRepository;
        $this->languageRepository = $languageRepository;
        $this->pimCategoryRepository = $pimCategoryRepository;
    }

    /**
     * @Route("/api/pim/categorydelete", name="api.action.pim.category.delete", methods={"GET"})
     */
    public function categoryDelete(Context $context): JsonResponse
    {
        //get value
        $categoryURL = $this->systemConfigService->get('PimImport.config.pimCategoryUrl');
        $apiKey = $this->systemConfigService->get('PimImport.config.pimApiKey');
        $dbSalesChannelId = $_GET['salesChannelId'];
        $generatedCategoryData = $manualCategoryData = [];

        if ($dbSalesChannelId) {
            $getAllSalesChannels = $this->getSalesChannelUsingId($dbSalesChannelId, $context);
            foreach ($getAllSalesChannels as $getAllSalesChannel) {
                $customFields = $getAllSalesChannel->getcustomFields();

                if (isset($customFields['custom_pim_sales_channel_publication_code'])) {
                    $PublicationCode = $customFields['custom_pim_sales_channel_publication_code'];
                    $mainCategoryID = $getAllSalesChannel->getnavigationCategoryId();
                    $subCategories = $this->getChildren($mainCategoryID, $context);
                    if ($subCategories) {
                        foreach ($subCategories as $subCategoryID => $subCategory) {
                            if (trim($subCategory->getName()) === 'Product' ||
                                trim($subCategory->getName()) === 'Products' ||
                                trim($subCategory->getName()) === 'Producten' ||
                                trim($subCategory->getName()) === 'Izdelki' ||
                                trim($subCategory->getName()) === 'Termékek' ||
                                trim($subCategory->getName()) === 'Produkte' ||
                                trim($subCategory->getName()) === 'Productos' ||
                                trim($subCategory->getName()) === 'Proizvodi' ||
                                trim($subCategory->getName()) === 'Produse' ||
                                trim($subCategory->getName()) === 'Produkty' ||
                                trim($subCategory->getName()) === 'Prodotti') {
                                $getTotalCategory = $this->getPIMapiData($categoryURL . '?' .
                                    $apiKey . '&filter=PublicationCode=' . $PublicationCode);
                                if (isset($getTotalCategory->Count)) {
                                    $apiUrl = $categoryURL . '/' . 1 . '/' . $getTotalCategory->Count . '?' .
                                        $apiKey . '&filter=PublicationCode=' . $PublicationCode;
                                    $categoryAPIData = $this->getPIMapiData($apiUrl);
                                    if (isset($categoryAPIData->PublicationNodes)) {
                                        $jumboGeneratedArray = $jumboManualArray = [];
                                        foreach ($categoryAPIData->PublicationNodes as $res) {
                                            $Origin = $res->Origin->Value;
                                            $Node = $res->Code->Value;
                                            if ($Origin === 'Generated' && $res->ProductGroupDetails) {
                                                if ($Node === "99") {
                                                    $subCategoryID = $mainCategoryID;
                                                }
                                                $categoryName = $res->ProductGroupDetails->Description->Value;
                                                $categoryId = $this->checkCategoryExist(
                                                    $categoryName,
                                                    $subCategoryID,
                                                    $context
                                                );
                                                if ($categoryId) {
                                                    $ChildNodes = $res->ChildNodes;
                                                    if ($ChildNodes) {
                                                        $generatedCategoryData = $this->checkCategoryChildId(
                                                            $ChildNodes,
                                                            $categoryId,
                                                            $context,
                                                            $generatedCategoryData
                                                        );
                                                    } else {
                                                        file_put_contents(
                                                            "DeleteCategoryImportLog.txt",
                                                            date("l jS \of F Y h:i:s A") .
                                                            "> Child Node Empty\n",
                                                            FILE_APPEND
                                                        );
                                                    }
                                                    (array)array_push(
                                                        $generatedCategoryData,
                                                        $categoryId
                                                    );
                                                    $generatedCategoryData = array_unique($generatedCategoryData);
                                                } else {
                                                    file_put_contents(
                                                        "DeleteCategoryImportLog.txt",
                                                        date("l jS \of F Y h:i:s A") .
                                                        "> New generated category come\n",
                                                        FILE_APPEND
                                                    );
                                                }
                                                $jumboGeneratedArray = (array)array_merge(
                                                    $jumboGeneratedArray,
                                                    $generatedCategoryData
                                                );
                                            }
                                            if ($Origin === 'Manual' && $res->Description) {
                                                if ($Node === "99") {
                                                    $subCategoryID = $mainCategoryID;
                                                }
                                                $MCategoryName = $res->Description->Value;
                                                $MCategoryId = $this->checkCategoryExist(
                                                    $MCategoryName,
                                                    $subCategoryID,
                                                    $context
                                                );
                                                if ($MCategoryId) {
                                                    $MChildNodes = $res->ChildNodes;
                                                    if ($MChildNodes) {
                                                        $manualCategoryData = $this->checkCategoryChildId(
                                                            $MChildNodes,
                                                            $MCategoryId,
                                                            $context,
                                                            $manualCategoryData
                                                        );
                                                    } else {
                                                        file_put_contents(
                                                            "DeleteCategoryImportLog.txt",
                                                            date("l jS \of F Y h:i:s A") . "> Child Node Empty\n",
                                                            FILE_APPEND
                                                        );
                                                    }
                                                    (array)array_push(
                                                        $manualCategoryData,
                                                        $MCategoryId
                                                    );
                                                    $manualCategoryData = array_unique($manualCategoryData);
                                                } else {
                                                    file_put_contents(
                                                        "DeleteCategoryImportLog.txt",
                                                        date("l jS \of F Y h:i:s A") . "> New manual category come\n",
                                                        FILE_APPEND
                                                    );
                                                }
                                                $jumboManualArray = (array)array_merge(
                                                    $jumboManualArray,
                                                    $manualCategoryData
                                                );
                                            }
                                        }
                                    } else {
                                        file_put_contents(
                                            "DeleteCategoryImportLog.txt",
                                            date("l jS \of F Y h:i:s A") . "> " . "APi Error1" . " End Delete\n",
                                            FILE_APPEND
                                        );
                                    }
                                } else {
                                    file_put_contents(
                                        "DeleteCategoryImportLog.txt",
                                        date("l jS \of F Y h:i:s A") . "> API Count Error\n",
                                        FILE_APPEND
                                    );
                                }
                            }
                        }
                    }
                }
            }

            if (!empty($jumboGeneratedArray) && !empty($jumboManualArray)) {
                $allCategoryData = array_merge($jumboGeneratedArray, $jumboManualArray);
                $allCategoryData = array_unique($allCategoryData);
                file_put_contents(
                    "DeleteCategoryImportLog.txt",
                    date("l jS \of F Y h:i:s A") . "> " . json_encode($allCategoryData) .
                    " Generated and Manual Category Data \n",
                    FILE_APPEND
                );
                $pimData = $this->checkPimCategoryData($dbSalesChannelId, $context);
                file_put_contents(
                    "DeleteCategoryImportLog.txt",
                    date("l jS \of F Y h:i:s A") . "> " . json_encode($pimData) .
                    " Delete pim Difference category manual cron\n",
                    FILE_APPEND
                );
                $finalDiff = array_diff($pimData, $allCategoryData);
                file_put_contents(
                    "DeleteCategoryImportLog.txt",
                    date("l jS \of F Y h:i:s A") . "> " . json_encode($finalDiff) .
                    " Delete Difference category manual cron\n",
                    FILE_APPEND
                );

                if (!empty($finalDiff)) {
                    $this->removeCategoryIds($finalDiff, $context);
                    return new JsonResponse([
                        'type' => 'success',
                        'message' => 'Category Deleted Successfully',
                    ]);
                }
                return new JsonResponse([
                    'type' => 'success',
                    'message' => 'Nothing delete any category because no found any difference.',
                ]);
            }
        }
        return new JsonResponse([
            'type' => 'error',
            'message' => 'Please select salesChannel',
        ]);
    }

    //get sales channel using id
    public function getSalesChannelUsingId($dbSalesChannelId, $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $dbSalesChannelId));
        return $this->salesChannelRepository->search($criteria, $context)->getEntities()->getElements();
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
    public function getPIMapiData($api_url)
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
        }
        
        return json_decode($response);
    }

    //check category
    public function checkCategoryExist(object $categoryName, string $parentId, Context $context): ?string
    {
        //set up the criteria for the search
        $languageKey = $this->getDefaultLanguageKey($context);
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
        if ($data->getName() === 'English') {
            $languageKey = 'en';
        }
        if ($data->getName() === 'Dutch' || $data->getName() === 'Nederlands') {
            $languageKey = 'nl';
        }
        if ($data->getName() === 'Deutsch') {
            $languageKey = 'de';
        }
        if ($data->getName() === 'Magyar') {
            $languageKey = 'hu';
        }
        if ($data->getName() === 'Italiano') {
            $languageKey = 'it';
        }
        if ($data->getName() === 'Croatian') {
            $languageKey = 'hr';
        }
        if ($data->getName() === 'Polski') {
            $languageKey = 'pl';
        }
        if ($data->getName() === 'Română') {
            $languageKey = 'ro';
        }
        if ($data->getName() === 'Slovenian') {
            $languageKey = 'sl';
        }
        if ($data->getName() === 'Español') {
            $languageKey = 'es';
        }
        if ($data->getName() === 'Čeština') {
            $languageKey = 'cs';
        }
        return $languageKey;
    }

    public function checkCategoryId($mainCategoryID, Context $context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('parentId', $mainCategoryID));
        $findCategoryData = $this->categoryRepository->search($criteria, $context)->getElements();
        $getChildIds = [];
        foreach ($findCategoryData as $data) {
            $variable = $data->id;
            $count = $data->childCount;
            if ($count > 0) {
                $getChildIds = $this->getParentCategoryId($variable, $context, $getChildIds);
            } else {
                array_push($getChildIds, $variable);
            }
        }
        return $getChildIds;
    }

    public function getParentCategoryId($variable, Context $context, $getChildIds)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('parentId', $variable));
        $findCategoryData1 = $this->categoryRepository->search($criteria, $context)->getElements();
        foreach ($findCategoryData1 as $value) {
            $variable = $value->id;
            $count = $value->childCount;
            if ($count > 0) {
                $getChildIds = $this->getParentCategoryId($variable, $context, $getChildIds);
            } else {
                array_push($getChildIds, $variable);
            }
        }
        return $getChildIds;
    }

    public function removeCategoryIds($finalDiff, Context $context)
    {
        foreach ($finalDiff as $categoryId) {
            $this->categoryRepository->delete([['id' => $categoryId]], $context);
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('categoryId', $categoryId));
            $Datas = $this->pimCategoryRepository->search($criteria, $context)->first();
            $this->pimCategoryRepository->delete([['id' => $Datas->id]], $context);
        }
    }

    public function checkPimCategoryData($dbSalesChannelId, Context  $context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $dbSalesChannelId));
        $Datas = $this->pimCategoryRepository->search($criteria, $context)->getElements();
        $allPimCategoryDataID = array();
        foreach ($Datas as $data) {
            $allPimCategoryDataID[] = $data->getCategoryId();
        }
        return $allPimCategoryDataID;
    }

    public function checkCategoryChildId($ChildNodes, $subCategoryID, $context, $allCategoryData)
    {
        foreach ($ChildNodes as $res) {
            $Origin = $res->Origin->Value;
            if ($Origin === 'Generated' && $res->ProductGroupDetails) {
                $categoryName = $res->ProductGroupDetails->Description->Value;
                $checkCategoryExist = $this->checkCategoryExist($categoryName, $subCategoryID, $context);
                if ($checkCategoryExist) {
                    $allCategoryData[] = $checkCategoryExist;
                    $categoryId = $checkCategoryExist;
                    $ChildNodes = $res->ChildNodes;
                    if ($ChildNodes) {
                        $subarray = $this->checkCategoryChildId($ChildNodes, $categoryId, $context, $allCategoryData);

                        $allCategoryData = array_merge($allCategoryData, $subarray);
                    } else {
                        file_put_contents(
                            "DeleteCategoryImportLog.txt",
                            date("l jS \of F Y h:i:s A") . "> " . "1" . " rGenerated category import manual cron\n",
                            FILE_APPEND
                        );
                    }
                }
            }

            if ($Origin === 'Manual' && $res->Description) {
                $MCategoryName = $res->Description->Value;
                $MCheckCategoryExist = $this->checkCategoryExist($MCategoryName, $subCategoryID, $context);
                if ($MCheckCategoryExist) {
                    $allCategoryData[] = $MCheckCategoryExist;
                    $MCategoryId = $MCheckCategoryExist;
                    $MChildNodes = $res->ChildNodes;
                    if ($MChildNodes) {
                        $subarray = $this->checkCategoryChildId($MChildNodes, $MCategoryId, $context, $allCategoryData);
                        $allCategoryData = array_merge($allCategoryData, $subarray);
                    }
                } else {
                    file_put_contents(
                        "DeleteCategoryImportLog.txt",
                        date("l jS \of F Y h:i:s A") . "> " . "1" . " Manual category import manual cron\n",
                        FILE_APPEND
                    );
                }
            }
        }
        return $allCategoryData;
    }
}
