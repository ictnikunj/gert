<?php declare(strict_types=1);

namespace PimImportCron\Service\ScheduledTask;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ProductPropertyCronTaskHandler extends ScheduledTaskHandler
{
    protected $scheduledTaskRepository;
    private SystemConfigService $systemConfigService;
    private EntityRepositoryInterface $languageRepository;
    private EntityRepositoryInterface $propertyRepository;
    private EntityRepositoryInterface $productsRepository;
    private EntityRepositoryInterface $propertyOptionsRepository;
    private EntityRepositoryInterface $productPropertyRepository;

    public function __construct(
        EntityRepositoryInterface $scheduledTaskRepository,
        SystemConfigService $systemConfigService,
        EntityRepositoryInterface $languageRepository,
        EntityRepositoryInterface $propertyRepository,
        EntityRepositoryInterface $productsRepository,
        EntityRepositoryInterface $propertyOptionsRepository,
        EntityRepositoryInterface $productPropertyRepository
    ) {
        $this->scheduledTaskRepository = $scheduledTaskRepository;
        $this->systemConfigService = $systemConfigService;
        $this->languageRepository = $languageRepository;
        $this->propertyRepository = $propertyRepository;
        $this->productsRepository = $productsRepository;
        $this->propertyOptionsRepository = $propertyOptionsRepository;
        $this->productPropertyRepository = $productPropertyRepository;
    }

    public static function getHandledMessages(): iterable
    {
        return [ ProductPropertyCronTask::class ];
    }

    public function run(): void
    {
        $context = Context::createDefaultContext();
        $apiDBUrl = $this->systemConfigService->get('PimImport.config.pimProductPropertyUrl');
        $apiParameters = $this->systemConfigService->get('PimImport.config.pimParameters');
        $apiKey = $this->systemConfigService->get('PimImport.config.pimApiKey');
        $getCronDataDateBase = $this->systemConfigService->get('PimImport.config.getCronDataDateBase');
        if ($getCronDataDateBase == '3Day') {
            $dataDate =  "&datesince=".date('Ymd', strtotime(' - 3 days'));
        } else {
            $dataDate = '';
        }
        $propertyValueDescription = null;
        echo $apiDBUrl . $apiParameters . $apiKey.$dataDate;
        $getTotalProperty = $this->getPIMApiData($apiDBUrl . $apiParameters . $apiKey.$dataDate);
        $lastCounter = $getTotalProperty->Count ?? '5000';
        $counter = 1;
        for ($i=1; $i<=$lastCounter; $i = $i+50) {
            $start_loop = $i;
            $apiURL = $apiDBUrl . '/'.$start_loop.'/' . '50' . $apiParameters . $apiKey.$dataDate;
            $propertyAPIData = $this->getPIMApiData($apiURL);
            file_put_contents("ProductPropertyImportLog.txt", date("l jS \of F Y h:i:s A")."> Start Cron Product Property Import\n", FILE_APPEND);
            if (isset($propertyAPIData->Products)) {
                foreach ($propertyAPIData->Products as $propertyData) {
                    echo $counter.'--';
                    file_put_contents("ProductPropertyImportLog.txt", date("l jS \of F Y h:i:s A")."> ".$counter." Counter\n", FILE_APPEND);
                    $productNumber = $propertyData->Code->Value;
                    $ProductFoundId = $this->checkProductExist($productNumber);
                    if ($ProductFoundId) {
                        $this->removeProductProperty($ProductFoundId, $context);
                        $j=0;
                        foreach ($propertyData->ProductFeatures as $ProData) {
                            if ($ProData->Domain == "Enum" || $ProData->Domain == "Boolean") {
                                $languageKey = $this->getDefaultLanguageKey($context);
                                $propertyValueDescription = $ProData->ValueDescription->$languageKey;
                            } elseif ($ProData->Domain == "Double") {
                                $propertyValueDescription = $ProData->Value.' '.$ProData->Unit;
                            } elseif ($ProData->Domain == "Range") {
                                $propertyValueDescription = $ProData->Value->Min.' to '.$ProData->Value->Max.' '.$ProData->Unit;
                            }
                            $PropertyFoundId = $this->checkPropertyExist($ProData->Description, $context);
                            if (!$PropertyFoundId) {
                                $propertyArray = array();
                                $PropertyFoundId = Uuid::randomHex();
                                $propertyArray['id'] = $PropertyFoundId;
                                $propertyArray['translations'] = $this->checkLanguage($ProData->Description, $context);
                                //file_put_contents("ProductPropertyImportLog.txt", date("l jS \of F Y h:i:s A") . "> " . serialize($propertyArray) . " Cron Category Import\n", FILE_APPEND);
                                $this->propertyInsert($propertyArray, $context);
                            }
                            $OptionFoundId = $this->checkOptionExist($PropertyFoundId, ($propertyValueDescription), $context);
                            if (!$OptionFoundId) {
                                $propertyArray = array();
                                $propertyArray['id'] = $PropertyFoundId;
                                $OptionFoundId = Uuid::randomHex();
                                $propertyArray['options'][$j] = array(
                                    'id' => $OptionFoundId,
                                    'position' => 1
                                );
                                if ($ProData->Domain == "Enum" || $ProData->Domain == "Boolean") {
                                    $propertyArray['options'][$j]['translations'] = $this->checkLanguage($ProData->ValueDescription, $context);
                                } elseif ($ProData->Domain == "Double") {
                                    $propertyArray['options'][$j]['name'] = $ProData->Value.' '.$ProData->Unit;
                                } elseif ($ProData->Domain == "Range") {
                                    $propertyArray['options'][$j]['name'] = $ProData->Value->Min.' to '.$ProData->Value->Max.' '.$ProData->Unit;
                                }
                                //file_put_contents("ProductPropertyImportLog.txt", date("l jS \of F Y h:i:s A") . "> " . serialize($propertyArray) . " Cron Category Import\n", FILE_APPEND);
                                $this->optionInsert($propertyArray, $context);
                            }
                            if ($ProductFoundId && $PropertyFoundId && $OptionFoundId) {
                                $propertyArray = array();
                                $propertyArray['id'] = $ProductFoundId;
                                $propertyArray['properties'][] = array(
                                    'id'=>$OptionFoundId,
                                    'groupId'=>$PropertyFoundId
                                );
                                //file_put_contents("ProductPropertyImportLog.txt", date("l jS \of F Y h:i:s A") . "> " . serialize($propertyArray) . " Cron Category Import\n", FILE_APPEND);
                                $this->productInsert($propertyArray, $context);
                            }

                            $j++;
                        }
                    }
                    $counter++;
                }
            }
            file_put_contents("ProductPropertyImportLog.txt", date("l jS \of F Y h:i:s A")."> End Cron Product Property Import\n", FILE_APPEND);
        }
    }
    //remove
    public function removeProductProperty($productId, $context): ?string
    {
        $productPropertycriteria = new Criteria();
        $productPropertycriteria->addFilter(new EqualsFilter('id', $productId));
        $productProperties = $this->productsRepository->search($productPropertycriteria, $context)->first();
        if ($productProperties->getpropertyIds()) {
            foreach ($productProperties->getpropertyIds() as $productProperties) {
                $propertycriteria = new Criteria();
                $propertycriteria->addFilter(new EqualsFilter('id', $productProperties));
                $propertycriteria->getAssociation('group')->addFilter(new EqualsFilter('customFields', null));
                $properties = $this->propertyOptionsRepository->search($propertycriteria, $context)->first();
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
            echo 'Error Curl Time Out Product Property Import';
            file_put_contents("ProductPropertyImportLog.txt", date("l jS \of F Y h:i:s A")."> Error Curl Time Out Product Property Product Import\n", FILE_APPEND);
            return "cURL Error #:" . $err;
        } else {
            return json_decode($response);
        }
    }

    // Insert Property
    public function propertyInsert(array $propertyData, Context $context)
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
        $datas = $this->languageRepository->search($criteria, $context)->getElements();
        foreach ($datas as $data) {
            if ($data->getName() == 'English') {
                $languageKey = 'en';
                if (isset($propertyData->$languageKey)) {
                    $languageData[$data->getId()]['name'] = ($propertyData->$languageKey);
                }
            }
            if ($data->getName() == 'Dutch' || $data->getName() == 'Nederlands') {
                $languageKey = 'nl';
                if (isset($propertyData->$languageKey)) {
                    $languageData[$data->getId()]['name'] = ($propertyData->$languageKey);
                }
            }
            if ($data->getName() == 'Deutsch') {
                $languageKey = 'de';
                if (isset($propertyData->$languageKey)) {
                    $languageData[$data->getId()]['name'] = ($propertyData->$languageKey);
                }
            }
            if ($data->getName() == 'French') {
                $languageKey = 'fr-FR';
                if (isset($propertyData->$languageKey)) {
                    $languageData[$data->getId()]['name'] = ($propertyData->$languageKey);
                }
            }
            if ($data->getName() == 'Magyar') {
                $languageKey = 'hu';
                if (isset($propertyData->$languageKey)) {
                    $languageData[$data->getId()]['name'] = ($propertyData->$languageKey);
                }
            }
            if ($data->getName() == 'Italiano') {
                $languageKey = 'it';
                if (isset($propertyData->$languageKey)) {
                    $languageData[$data->getId()]['name'] = ($propertyData->$languageKey);
                }
            }
            if ($data->getName() == 'Croatian') {
                $languageKey = 'hr';
                $languageData[$data->getId()]['name'] = ($propertyData->$languageKey);
            }
            if ($data->getName() == 'Polski') {
                $languageKey = 'pl';
                if (isset($propertyData->$languageKey)) {
                    $languageData[$data->getId()]['name'] = ($propertyData->$languageKey);
                }
            }
            if ($data->getName() == 'Română') {
                $languageKey = 'ro';
                if (isset($propertyData->$languageKey)) {
                    $languageData[$data->getId()]['name'] = ($propertyData->$languageKey);
                }
            }
            if ($data->getName() == 'Slovenian') {
                $languageKey = 'sl';
                if (isset($propertyData->$languageKey)) {
                    $languageData[$data->getId()]['name'] = ($propertyData->$languageKey);
                }
            }
            if ($data->getName() == 'Español') {
                $languageKey = 'es';
                if (isset($propertyData->$languageKey)) {
                    $languageData[$data->getId()]['name'] = ($propertyData->$languageKey);
                }
            }
            if ($data->getName() == 'Čeština') {
                $languageKey = 'cs';
                if (isset($propertyData->$languageKey)) {
                    $languageData[$data->getId()]['name'] = ($propertyData->$languageKey);
                }
            }
        }
        return $languageData;
    }

    public function optionInsert($propertyArray, Context $context)
    {
        $data = $propertyArray;
        $this->propertyRepository->upsert([$data], $context);
    }

    public function productInsert($propertyArray, Context $context)
    {
        $data = $propertyArray;
        $this->productsRepository->upsert([$data], $context);
    }
}
