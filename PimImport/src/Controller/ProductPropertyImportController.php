<?php declare(strict_types=1);

namespace PimImport\Controller;

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
class ProductPropertyImportController extends AbstractController
{
    /*** @var SystemConfigService */
    private $systemConfigService;

    /*** @var EntityRepositoryInterface */
    private $languageRepository;

    /*** @var EntityRepositoryInterface */
    private $propertyRepository;

    /*** @var EntityRepositoryInterface */
    private $productsRepository;

    /*** @var EntityRepositoryInterface */
    private $propertyOptionsRepository;

    /*** @var EntityRepositoryInterface */
    private $productPropertyRepository;

    public function __construct(
        SystemConfigService $systemConfigService,
        EntityRepositoryInterface $languageRepository,
        EntityRepositoryInterface $propertyRepository,
        EntityRepositoryInterface $productsRepository,
        EntityRepositoryInterface $propertyOptionsRepository,
        EntityRepositoryInterface $productPropertyRepository
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->languageRepository = $languageRepository;
        $this->propertyRepository = $propertyRepository;
        $this->productsRepository = $productsRepository;
        $this->propertyOptionsRepository = $propertyOptionsRepository;
        $this->productPropertyRepository = $productPropertyRepository;
    }

    /**
     * @Route("/api/pim/ProductPropertyImport", name="api.action.pim.product.property.import", methods={"GET"})
     * @param Context $context
     * @param null $cron
     * @param null $cronCounter
     * @return JsonResponse
     */
    public function productPropertyImport(
        Context $context,
        $cron = null,
        $cronCounter = null
    ): JsonResponse {
        //get config
        $apiDBUrl = $this->systemConfigService->get('PimImport.config.pimProductPropertyUrl');
        $apiParameters = $this->systemConfigService->get('PimImport.config.pimParameters');
        $apiKey = $this->systemConfigService->get('PimImport.config.pimApiKey');

        //initialize variable
        $propertyValueDescription = null;

        //decide start and end counter
        $getTotalProperty = $this->getPIMApiData($apiDBUrl . $apiParameters . $apiKey);
        $lastCounter = $getTotalProperty->Count ?? 1;
        if ($cron === 1) {
            $counter = 1;
            $perPage = $cronCounter;
        } else {
            $counter = $_GET['counter'];
            $perPage = 1;
        }

        $apiUrl = $apiDBUrl . '/' . $counter . '/' . $perPage . $apiParameters . $apiKey;
        $propertyAPIData = $this->getPIMApiData($apiUrl);
        if (isset($propertyAPIData->Products)) {
            foreach ($propertyAPIData->Products as $propertyData) {
                $productNumber = $propertyData->Code->Value;
                $ProductFoundId = $this->checkProductExist($productNumber);
                if ($ProductFoundId) {
                    //reset product data so first remove and again insert
                    $this->removeProductProperty($ProductFoundId, $context);

                    $j=0;
                    foreach ($propertyData->ProductFeatures as $ProData) {
                        //define value based on type
                        if ($ProData->Domain === "Enum" || $ProData->Domain === "Boolean") {
                            $languageKey = $this->getDefaultLanguageKey($context);
                            $propertyValueDescription = $ProData->ValueDescription->$languageKey;
                        } elseif ($ProData->Domain === "Double") {
                            $propertyValueDescription = $ProData->Value.' '.$ProData->Unit;
                        } elseif ($ProData->Domain === "Range") {
                            $propertyValueDescription = $ProData->Value->Min.' to '.$ProData->Value->Max.' '.$ProData->Unit;
                        }

                        //check property exist or not
                        $PropertyFoundId = $this->checkPropertyExist($ProData->Description, $context);
                        if (!$PropertyFoundId) {
                            $propertyArray = array();
                            $PropertyFoundId = Uuid::randomHex();
                            $propertyArray['id'] = $PropertyFoundId;
                            $propertyArray['translations'] = $this->checkLanguage($ProData->Description, $context);
                            if ($cron === 1) {
                                file_put_contents(
                                    "productPropertyImportLog.txt",
                                    date("l jS \of F Y h:i:s A")."> ".serialize($propertyArray)." Cron Product Property Log Import\n",
                                    FILE_APPEND
                                );
                            }
                            $this->propertyInsert($propertyArray, $context);
                        }

                        //check option exist or not
                        $OptionFoundId = $this->checkOptionExist($PropertyFoundId, $propertyValueDescription, $context);
                        if (!$OptionFoundId) {
                            $propertyArray = [];
                            $propertyArray['id'] = $PropertyFoundId;
                            $OptionFoundId = Uuid::randomHex();
                            $propertyArray['options'][$j] = array(
                                'id' => $OptionFoundId,
                                'position' => 1
                            );
                            //define value based on type
                            if ($ProData->Domain === "Enum" || $ProData->Domain === "Boolean") {
                                $propertyArray['options'][$j]['translations'] = $this->checkLanguage($ProData->ValueDescription, $context);
                            } elseif ($ProData->Domain === "Double") {
                                $propertyArray['options'][$j]['name'] = $ProData->Value.' '.$ProData->Unit;
                            } elseif ($ProData->Domain === "Range") {
                                $propertyArray['options'][$j]['name'] = $ProData->Value->Min.' to '.$ProData->Value->Max.' '.$ProData->Unit;
                            }
                            if ($cron === 1) {
                                file_put_contents(
                                    "productPropertyImportLog.txt",
                                    date("l jS \of F Y h:i:s A")."> ".serialize($propertyArray)." Cron Product Property Log Import\n",
                                    FILE_APPEND
                                );
                            }
                            $this->optionInsert($propertyArray, $context);
                        }

                        //insert property into product
                        if ($ProductFoundId && $PropertyFoundId && $OptionFoundId) {
                            $propertyArray = array();
                            $propertyArray['id'] = $ProductFoundId;
                            $propertyArray['properties'][] = array(
                                'id'=>$OptionFoundId,
                                'groupId'=>$PropertyFoundId
                            );
                            if ($cron === 1) {
                                file_put_contents(
                                    "productPropertyImportLog.txt",
                                    date("l jS \of F Y h:i:s A"). "> ".serialize($propertyArray)." Cron Product Property Log Import\n",
                                    FILE_APPEND
                                );
                            }
                            $this->productInsert($propertyArray, $context);
                        }
                        $j++;
                    }
                }
                $counter++;
                $this->systemConfigService->set('PimImport.config.ProductPropertyCounter', $counter);
            }

            //reset counter
            if ($counter === $lastCounter + 1) {
                $this->systemConfigService->set('PimImport.config.ProductPropertyCounter', 1);
            }
        }

        return new JsonResponse([
            'type'=>'success',
            'message' => 'Success',
            'counter' => $counter,
            'ActualData'=>$lastCounter,
            'endCounter'=> $lastCounter+1,
        ]);
    }

    //get api data
    public function getPIMApiData($api_url)
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

    //Remove
    public function removeProductProperty($productId, $context): ?string
    {
        $productPropertyCriteria = new Criteria();
        $productPropertyCriteria->addFilter(new EqualsFilter('id', $productId));
        $productProperties = $this->productsRepository->search($productPropertyCriteria, $context)->first();
        if ($productProperties->getpropertyIds()) {
            foreach ($productProperties->getpropertyIds() as $productProperties) {
                $propertyCriteria = new Criteria();
                $propertyCriteria->addFilter(new EqualsFilter('id', $productProperties));
                $propertyCriteria->getAssociation('group')->addFilter(new EqualsFilter('customFields', null));
                $properties = $this->propertyOptionsRepository->search($propertyCriteria, $context)->first();
                if (empty($properties->getGroup()->getcustomFields())) {
                    $this->productPropertyRepository->delete([
                        [
                            'productId' => $productId,
                            'optionId' => $productProperties
                        ]
                    ], $context);
                }
            }
        }
        return $productId;
    }


    // Insert Property
    public function propertyInsert(array $propertyData, Context $context): void
    {
        $data = [
            'id' => $propertyData['id'],
            'filterable' => false
        ];

        mail('gert-jan@gj-r.nl', 'Change etim import', json_encode($propertyData));

        if (isset($propertyData['translations'])) {
            $data['translations'] = $propertyData['translations'];
        }
        if (isset($propertyData['options'])) {
            $data['options'] = $propertyData['options'];
        }
        if (isset($propertyData['custom_property_api_name'])) {
            $data['customFields'] = ['custom_property_api_name_field'=>$propertyData['custom_property_api_name']];
        }
        $this->propertyRepository->upsert([$data], $context);
        return;
    }

    // Check Updated Product
    public function checkProductExist($productNumber = null): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productNumber', $productNumber));
        return $this->productsRepository->searchIds($criteria, Context::createDefaultContext())->firstId();
    }

    //check property
    public function checkPropertyExist($property, Context $context): ?string
    {
        //set up the criteria for the search
        $languageKey = $this->getDefaultLanguageKey($context);
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', ($property->$languageKey)));
        return $this->propertyRepository->searchIds($criteria, $context)->firstId();
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

    //check option
    public function checkOptionExist($propertyId, $property, Context $context): ?string
    {
        //set up the criteria for the search
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('groupId', $propertyId));
        $criteria->addFilter(new EqualsFilter('name', $property));
        return $this->propertyOptionsRepository->searchIds($criteria, $context)->firstId();
    }

    // Check the Language
    public function checkLanguage($propertyData, Context $context): array
    {
        $languageData = array();
        $criteria = new Criteria();
        $criteria->addAssociation('locale');
        foreach ($this->languageRepository->search($criteria, $context)->getElements() as $data) {
            if ($data->getName() === 'English') {
                $languageKey = 'en';
                if (isset($propertyData->$languageKey)) {
                    $languageData[$data->getId()]['name'] = ($propertyData->$languageKey);
                }
            }
            if ($data->getName() === 'Dutch' || $data->getName() === 'Nederlands') {
                $languageKey = 'nl';
                if (isset($propertyData->$languageKey)) {
                    $languageData[$data->getId()]['name'] = ($propertyData->$languageKey);
                }
            }
            if ($data->getName() === 'Deutsch') {
                $languageKey = 'de';
                if (isset($propertyData->$languageKey)) {
                    $languageData[$data->getId()]['name'] = ($propertyData->$languageKey);
                }
            }
            if ($data->getName() === 'French') {
                $languageKey = 'fr-FR';
                if (isset($propertyData->$languageKey)) {
                    $languageData[$data->getId()]['name'] = ($propertyData->$languageKey);
                }
            }
            if ($data->getName() === 'Magyar') {
                $languageKey = 'hu';
                if (isset($propertyData->$languageKey)) {
                    $languageData[$data->getId()]['name'] = ($propertyData->$languageKey);
                }
            }
            if ($data->getName() === 'Italiano') {
                $languageKey = 'it';
                if (isset($propertyData->$languageKey)) {
                    $languageData[$data->getId()]['name'] = ($propertyData->$languageKey);
                }
            }
            if ($data->getName() === 'Croatian') {
                $languageKey = 'hr';
                $languageData[$data->getId()]['name'] = ($propertyData->$languageKey);
            }
            if ($data->getName() === 'Polski') {
                $languageKey = 'pl';
                if (isset($propertyData->$languageKey)) {
                    $languageData[$data->getId()]['name'] = ($propertyData->$languageKey);
                }
            }
            if ($data->getName() === 'Română') {
                $languageKey = 'ro';
                if (isset($propertyData->$languageKey)) {
                    $languageData[$data->getId()]['name'] = ($propertyData->$languageKey);
                }
            }
            if ($data->getName() === 'Slovenian') {
                $languageKey = 'sl';
                if (isset($propertyData->$languageKey)) {
                    $languageData[$data->getId()]['name'] = ($propertyData->$languageKey);
                }
            }
            if ($data->getName() === 'Español') {
                $languageKey = 'es';
                if (isset($propertyData->$languageKey)) {
                    $languageData[$data->getId()]['name'] = ($propertyData->$languageKey);
                }
            }
            if ($data->getName() === 'Čeština') {
                $languageKey = 'cs';
                if (isset($propertyData->$languageKey)) {
                    $languageData[$data->getId()]['name'] = ($propertyData->$languageKey);
                }
            }
        }
        return $languageData;
    }

    public function optionInsert($propertyArray, Context $context): void
    {
        $data = $propertyArray;
        $this->propertyRepository->upsert([$data], $context);
        return;
    }

    public function productInsert($propertyArray, Context $context): void
    {
        $this->productsRepository->upsert([$propertyArray], $context);
        return;
    }
}
