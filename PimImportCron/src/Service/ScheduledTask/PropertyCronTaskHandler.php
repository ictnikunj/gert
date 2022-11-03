<?php declare(strict_types=1);

namespace PimImportCron\Service\ScheduledTask;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class PropertyCronTaskHandler extends ScheduledTaskHandler
{
    protected $scheduledTaskRepository;
    private SystemConfigService $systemConfigService;
    private EntityRepositoryInterface $languageRepository;
    private EntityRepositoryInterface $propertyRepository;

    public function __construct(
        EntityRepositoryInterface $scheduledTaskRepository,
        SystemConfigService $systemConfigService,
        EntityRepositoryInterface $languageRepository,
        EntityRepositoryInterface $propertyRepository
    ) {
        $this->scheduledTaskRepository = $scheduledTaskRepository;
        $this->systemConfigService = $systemConfigService;
        $this->languageRepository = $languageRepository;
        $this->propertyRepository = $propertyRepository;
    }

    public static function getHandledMessages(): iterable
    {
        return [ PropertyCronTask::class ];
    }

    public function run(): void
    {
        $context = Context::createDefaultContext();
        $propertyURL = $this->systemConfigService->get('PimImport.config.pimPropertyUrl');
        $apiParameters = '?';
        $apiKey = $this->systemConfigService->get('PimImport.config.pimApiKey');
        echo $propertyURL . $apiParameters . $apiKey . '&filter=EntityName=Prd.Product';
        $getTotalProperty = $this->getPIMApiData($propertyURL . $apiParameters . $apiKey . '&filter=EntityName=Prd.Product');
        $lastCounter = $getTotalProperty->Count ?? '14';
        $counter = 1;
        $UDFArray = array('UDF_PRD_ProductWarranty','UDF_DoP','UDF_Reservoir_Types','UDF_Series','UDF_PRD_ProductBackorderWarranty','UDF_PRD_AccessibleProducts');
        $apiUrl = $propertyURL . '/' . $counter . '/' . $lastCounter . $apiParameters . $apiKey . '&filter=EntityName=Prd.Product';
        $propertyAPIData = $this->getPIMApiData($apiUrl);
        if(isset($propertyAPIData->UserDefFields)){
            file_put_contents("PropertyImportLog.txt",date("l jS \of F Y h:i:s A")."> Start Cron Property Import\n",FILE_APPEND);
            foreach($propertyAPIData->UserDefFields as $propertyData){
                echo $counter.'--';
                file_put_contents("PropertyImportLog.txt",date("l jS \of F Y h:i:s A")."> ".$counter." Property Import\n",FILE_APPEND);
                $propertyArray = array();
                $name = $propertyData->Name->Value;
                if(in_array($name,$UDFArray)){
                    $propertyArray['id'] = Uuid::randomHex();
                    $propertyArray['custom_property_api_name'] = $name;
                    $propertyArray['filterable'] = '0';
                    $checkPropertyExist = $this->checkPropertyExist($propertyData->Label->Value,$context);
                    if (!$checkPropertyExist) {
                        $propertyArray['translations'] = $this->checkLanguage($propertyData->Label->Value, $context);
                        if (isset($propertyData->UserDefFieldOptions)) {
                            if(!$propertyData->UserDefFieldOptions){
                                $propertyData->UserDefFieldOptions = array(
                                    (object)array(
                                        'Label'=>(Object)array(
                                            'Value'=>(Object)array(
                                                'en'=>'true',
                                                'nl'=>'Ja',
                                                'de'=>'Ja',
                                                'hu'=>'Ja',
                                                'it'=>'Yes',
                                                'hr'=>'Ja',
                                                'pl'=>'Ja',
                                                'ro'=>'Da',
                                                'sl'=>'Ja',
                                                'es'=>'Yes',
                                                'cs'=>'Ja'
                                            )
                                        ),
                                        'SortOrder'=>(Object)array(
                                            'Value'=>1
                                        )
                                    ),
                                    (object)array(
                                        'Label'=>(Object)array(
                                            'Value'=>(Object)array(
                                                'en'=>'false',
                                                'nl'=>'Nee',
                                                'de'=>'Nein',
                                                'hu'=>'Nee',
                                                'it'=>'No',
                                                'hr'=>'Nee',
                                                'pl'=>'Nee',
                                                'ro'=>'Nu',
                                                'sl'=>'Nee',
                                                'es'=>'No',
                                                'cs'=>'Nee'
                                            )
                                        ),
                                        'SortOrder'=>(Object)array(
                                            'Value'=>1
                                        )
                                    ),
                                );
                            }
                            foreach ($propertyData->UserDefFieldOptions as $propertyOption) {
                                $optionId = Uuid::randomHex();
                                $propertyArray['options'][] = array(
                                    'id' => $optionId,
                                    'translations' => $this->checkLanguage($propertyOption->Label->Value, $context),
                                    'position' => $propertyOption->SortOrder->Value,
                                );
                            }
                        }
                        $this->propertyInsert($propertyArray, $context);
                    }
                }
                $counter++;
            }
            file_put_contents("PropertyImportLog.txt",date("l jS \of F Y h:i:s A")."> End Cron Property Import\n",FILE_APPEND);
        }
    }

    public function getPIMApiData($api_url){
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
            echo 'Error Curl Time Out Property Import';
            file_put_contents("PropertyImportLog.txt",date("l jS \of F Y h:i:s A")."> Error Curl Time Out Property Import\n",FILE_APPEND);
            return "cURL Error #:" . $err;
        } else {
            return json_decode($response);
        }
    }

    public function propertyInsert(array $propertyData,Context $context)
    {
        $data = [
            'id' => $propertyData['id'],
            'translations' => $propertyData['translations'],
            'filterable' => false
        ];

        if(isset($propertyData['custom_property_api_name'])){
            $data['customFields'] = ['custom_property_api_name_field'=>$propertyData['custom_property_api_name']];
        }
        if(isset($propertyData['options'])){
            $data['options'] = $propertyData['options'];
        }
        $this->propertyRepository->upsert([$data],$context);
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

    public function checkPropertyExist (Object $property,Context $context): ?string
    {
        //set up the criteria for the search
        $languageKey = $this->getDefaultLanguageKey($context);
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $property->$languageKey));
        return $this->propertyRepository->searchIds($criteria, $context)->firstId();
    }

    public function checkLanguage($propertyData,Context $context): array
    {
        $languageData = array();
        $criteria = new Criteria();
        $datas = $this->languageRepository->search($criteria, $context)->getElements();
        foreach ($datas as $data) {
            if ($data->getName() == 'English') {
                $languageKey = 'en';
                $languageData[$data->getId()]['name'] = $propertyData->$languageKey;
            }
            if($data->getName() == 'Dutch' || $data->getName() == 'Nederlands'){
                $languageKey = 'nl';
                $languageData[$data->getId()]['name'] = $propertyData->$languageKey;
            }
            if($data->getName() == 'Deutsch'){
                $languageKey = 'de';
                $languageData[$data->getId()]['name'] = $propertyData->$languageKey;
            }
            if($data->getName() == 'Magyar'){
                $languageKey = 'hu';
                $languageData[$data->getId()]['name'] = $propertyData->$languageKey;
            }
            if($data->getName() == 'Italiano'){
                $languageKey = 'it';
                $languageData[$data->getId()]['name'] = $propertyData->$languageKey;
            }
            if($data->getName() == 'Croatian'){
                $languageKey = 'hr';
                $languageData[$data->getId()]['name'] = $propertyData->$languageKey;
            }
            if($data->getName() == 'Polski'){
                $languageKey = 'pl';
                $languageData[$data->getId()]['name'] = $propertyData->$languageKey;
            }
            if($data->getName() == 'Română'){
                $languageKey = 'ro';
                $languageData[$data->getId()]['name'] = $propertyData->$languageKey;
            }
            if($data->getName() == 'Slovenian'){
                $languageKey = 'sl';
                $languageData[$data->getId()]['name'] = $propertyData->$languageKey;
            }
            if($data->getName() == 'Español'){
                $languageKey = 'es';
                $languageData[$data->getId()]['name'] = $propertyData->$languageKey;
            }
            if($data->getName() == 'Čeština'){
                $languageKey = 'cs';
                $languageData[$data->getId()]['name'] = $propertyData->$languageKey;
            }
        }
        return $languageData;
    }
}
