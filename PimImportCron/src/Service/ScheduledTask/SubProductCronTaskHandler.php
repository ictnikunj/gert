<?php declare(strict_types=1);

namespace PimImportCron\Service\ScheduledTask;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\JsonResponse;

class SubProductCronTaskHandler extends ScheduledTaskHandler
{
    protected $scheduledTaskRepository;
    private SystemConfigService $systemConfigService;
    private EntityRepositoryInterface $productsRepository;
    private EntityRepositoryInterface $pimProductRepository;
    private EntityRepositoryInterface $productCrossSellingAssignedProductsRepository;

    public function __construct(
        EntityRepositoryInterface $scheduledTaskRepository,
        SystemConfigService $systemConfigService,
        EntityRepositoryInterface $productsRepository,
        EntityRepositoryInterface $pimProductRepository,
        EntityRepositoryInterface $productCrossSellingAssignedProductsRepository
    ) {
        $this->scheduledTaskRepository = $scheduledTaskRepository;
        $this->systemConfigService = $systemConfigService;
        $this->productsRepository = $productsRepository;
        $this->pimProductRepository = $pimProductRepository;
        $this->productCrossSellingAssignedProductsRepository = $productCrossSellingAssignedProductsRepository;
    }

    public static function getHandledMessages(): iterable
    {
        return [ SubProductCronTask::class ];
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
        for($i=1;$i<=$last_counter;$i = $i+50) {
            $start_loop = $i;
            $apiURL = $crossUrl . '/' . $start_loop . '/' . '50' . $apiParameters . $apiKey.$dataDate;
            $response = $this->getCurlData($apiURL);
            if(isset($response->Products)) {
                file_put_contents("SubImportLog.txt",date("l jS \of F Y h:i:s A")."> Start Cron Sub Product Import\n",FILE_APPEND);
                foreach ($response->Products as $res) {
                    echo $counter.'--';
                    file_put_contents("SubImportLog.txt",date("l jS \of F Y h:i:s A")."> '.$counter.' Sub Product Import\n",FILE_APPEND);
                    $crossSellingID = '';
                    $productNumber = isset($res->Code) ? $res->Code->Value : '';
                    $getProductData = $this->getProductData($productNumber, $context);
                    if($getProductData != null){
                        $productID = $this->getProductData($productNumber, $context)->getId();
                        $flag = false;
                        $checkDate = isset($res->LastModificationDateTime) ? $res->LastModificationDateTime->Value : '';
                        $date = date('Y-m-d H:i:s', strtotime($checkDate));
                        $checkUpdatedProduct = $this->checkUpdatedProduct($date, $productNumber);
                        if (empty($checkUpdatedProduct)) {
                            $crossSelling = $this->getProductData($productNumber, $context)->getcrossSellings()->getElements();
                            if ($crossSelling) {
                                foreach ($crossSelling as $crossData) {
                                    if ($crossData->getName() == 'Product Parts') {
                                        $crossSellingID = $crossData->getId();
                                    }
                                }
                            }
                            if ($crossSellingID) {
                                $this->deleteCrossSellData($productID, $crossSellingID, $context);
                                if ($res->SubProducts) {
                                    $i = 1;
                                    foreach ($res->SubProducts as $subProduct) {
                                        $productPartNumber = $subProduct->SubCode->Value;
                                        $productPartSequenceNo = $subproduct->SequenceNo->Value;
                                        if (!empty($this->checkProductNumber($productPartNumber))) {
                                            $productPartID = $this->getProductData($productPartNumber, $context)->getId();
                                            if ($productPartID) {
                                                $i++;
                                                $data = [
                                                    'crossSellingId' => $crossSellingID,
                                                    'productId' => $productPartID,
                                                    'position' => $productPartSequenceNo?$productPartSequenceNo:$i,
                                                ];
                                                $this->productCrossSellingAssignedProductsRepository->upsert([$data], Context::createDefaultContext());
                                                $flag = true;
                                            }
                                        }
                                    }
                                }
                                if ($flag == true) {
                                    $PimID = $this->checkPimID($productNumber);
                                    $pimData = [
                                        'id' => !empty($PimID) ? $PimID->getId() : Uuid::randomHex(),
                                        'productNumber' => $productNumber,
                                        'lastProductPartCrossSellUsage' => $checkDate,
                                    ];
                                    $this->pimProductRepository->upsert([$pimData], $context);
                                }
                            }
                        }
                    }
                    $counter++;
                }
                file_put_contents("SubImportLog.txt",date("l jS \of F Y h:i:s A")."> End Cron Sub Product Import\n",FILE_APPEND);
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
            echo 'Error Curl Time Out Sub Product Import';
            file_put_contents("SubImportLog.txt",date("l jS \of F Y h:i:s A")."> Error Curl Time Out Sub Product Import\n",FILE_APPEND);
            return new JsonResponse(['type'=>'error','message' =>  $err]);
        } else {
            return json_decode($response);
        }
    }

    public function checkUpdatedProduct($date = null, $productNumber = null): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('lastProductPartCrossSellUsage',$date));
        $criteria->addFilter(new EqualsFilter('productNumber',$productNumber));
        return $this->pimProductRepository->searchIds($criteria, Context::createDefaultContext())->firstId();
    }

    public function checkPimID($productNumber = null)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productNumber',$productNumber));
        return $this->pimProductRepository->search($criteria, Context::createDefaultContext())->first();
    }

    public function getProductData($productNumber = null,$context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productNumber',$productNumber));
        $criteria->addAssociation('crossSellings');
        $productObject = $this->productsRepository->search($criteria, $context);
        if($productObject->getTotal() == 0){
            return null;
        }else{
            return $productObject->first();
        }
    }

    public function checkProductNumber($productNumber = null)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productNumber',$productNumber));
        return $this->productsRepository->search($criteria, Context::createDefaultContext())->first();
    }

    public function deleteCrossSellData($productID = null,$crossSellingID = null,$context): bool
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('crossSellingId', $crossSellingID));
        $allData = $this->productCrossSellingAssignedProductsRepository->searchIds($criteria,$context)->getIds();
        if($allData){
            foreach($allData as $crossId){
                if($crossId) {
                    $this->productCrossSellingAssignedProductsRepository->delete(
                        [
                            [
                                'id' => $crossId,
                            ]
                        ],
                        Context::createDefaultContext()
                    );
                }
            }
        }
        return true;
    }
}
