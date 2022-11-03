<?php declare(strict_types=1);

namespace PimImport\Controller;

use Shopware\Core\Content\Product\Aggregate\ProductCrossSelling\ProductCrossSellingDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

/**
 * @RouteScope(scopes={"api"})
 */
class ProductCrossImportController extends AbstractController
{
    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * @var EntityRepositoryInterface
     */
    private $productsRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $languageRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $pimProductRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $productCrossSellingRepository;
    /**
     * @var EntityRepositoryInterface
     */
    private $productCrossSellingAssignedProductsRepository;

    public function __construct(
        SystemConfigService $systemConfigService,
        EntityRepositoryInterface $productsRepository,
        EntityRepositoryInterface $languageRepository,
        EntityRepositoryInterface $pimProductRepository,
        EntityRepositoryInterface $productCrossSellingRepository,
        EntityRepositoryInterface $productCrossSellingAssignedProductsRepository
    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->productsRepository = $productsRepository;
        $this->languageRepository = $languageRepository;
        $this->pimProductRepository = $pimProductRepository;
        $this->productCrossSellingRepository = $productCrossSellingRepository;
        $this->productCrossSellingAssignedProductsRepository = $productCrossSellingAssignedProductsRepository;
    }

    /**
     * @Route("/api/pim/productcrossselling", name="api.action.pim.product.cross.import", methods={"GET"})
     */
    public function productCrossImport($val = null,Context $context): JsonResponse
    {
        $url = $this->systemConfigService->get('PimImport.config.pimUrl');
        $apiKey = $this->systemConfigService->get('PimImport.config.pimApiKey');
        $apiParameters = $this->systemConfigService->get('PimImport.config.pimParameters');
        $crossurl = $this->systemConfigService->get('PimImport.config.pimCrossUrl');
        $relatedProductCounter = $this->systemConfigService->get('PimImport.config.relatedProductCounter');

        // PHP Curl for count get
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => $crossurl.$apiParameters.$apiKey,
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
            return new JsonResponse(['type'=>'error','message' =>  $err]);
        } else {
            $response = json_decode($response);

            $last_counter = isset($response->Count) ? $response->Count : '10';

            if($val == 1){
                $start_loop = 1;
                $response->Count = 10;
                $counter = 1;
            }else{
                if(isset($_GET['counter'])){
                    $start_loop = $_GET['counter'];
                    $response->Count = 1;
                    $counter = $_GET['counter'];
                }else {
                    $start_loop = 1;
                    $response->Count = 10;
                    $counter = 1;
                }
            }

            // PHP Curl for all data get
            $curl = curl_init();
            curl_setopt_array($curl, array(
            CURLOPT_URL => $crossurl.'/'.$start_loop.'/'.$response->Count.$apiParameters.$apiKey,
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

                return new JsonResponse(['type'=>'error','message' =>  $err]);

            } else {

                $response = json_decode($response);
                if(isset($response->Products)){
                    foreach ($response->Products as $key => $res) {

                        if (!empty($res->OwnAlternatives)) {
                            $crossSellingID = '';

                            //get product number and id
                            $productNumber = isset($res->Code) ? $res->Code->Value : '';
                            $productID = $this->getProductData($productNumber, $context)->getId();

                            //check product modified or not
                            $flag = false;
                            $checkdate = isset($res->LastModificationDateTime) ? $res->LastModificationDateTime->Value : '';
                            $date = date('Y-m-d H:i:s', strtotime($checkdate));
                            $checkUpdatedProduct = $this->checkUpdatedProduct($date, $productNumber);

                            if (empty($checkUpdatedProduct)) {
                                //get cross selling id
                                $crossSelling = $this->getProductData($productNumber, $context)->getcrossSellings()->getElements();
                                if ($crossSelling) {
                                    foreach ($crossSelling as $crossdata) {
                                        if ($crossdata->getName() == 'Related Items') {
                                            $crossSellingID = $crossdata->getId();
                                        }
                                    }
                                }

                                if ($crossSellingID != '') {

                                    //delete all related cross sell data
                                    $this->deleteCrossSellData($productID, $crossSellingID, $context);

                                    if (!empty($res->OwnAlternatives)) {

                                        $i = 1;
                                        foreach ($res->OwnAlternatives as $key => $relatedProduct) {

                                            $relatedNumber = $relatedProduct->PAProductCode->Value;

                                            //check related product available in main product
                                            if (!empty($this->checkProductNumber($relatedNumber))) {

                                                $relatedProductID = $this->getProductData($relatedNumber, $context)->getId();
                                                if ($relatedProductID) {
                                                    //insert in cross sell related product
                                                    $data = [
                                                        'crossSellingId' => $crossSellingID,
                                                        'productId' => $relatedProductID,
                                                        'position' => $i++,
                                                    ];
                                                    $this->productCrossSellingAssignedProductsRepository->upsert([$data], Context::createDefaultContext());
                                                    $flag = true;
                                                }

                                            }
                                        }
                                    }
                                    if ($flag == true) {
                                        //check the pimData
                                        $PimID = $this->checkPimID($productNumber);

                                        // Pip Import Data
                                        $pimData = [
                                            'id' => !empty($PimID) ? $PimID->getId() : Uuid::randomHex(),
                                            'productNumber' => $productNumber,
                                            'lastRelatedCrossSellUsage' => $checkdate,
                                        ];
                                        $this->pimProductRepository->upsert([$pimData], $context);
                                    }
                                }

                            }
                        }
                        $counter++;
                    }
                }
            }
        }
        $this->systemConfigService->set('PimImport.config.relatedProductCounter',$counter);
        return new JsonResponse(['type'=>'success','message' => 'Success','counter' => $counter,'endcounter'=> $last_counter+1]);
    }

    // Check Updated Product
    public function checkUpdatedProduct($date = null, $productNumber = null)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('lastRelatedCrossSellUsage',$date));
        $criteria->addFilter(new EqualsFilter('productNumber',$productNumber));
        return $this->pimProductRepository->searchIds($criteria, Context::createDefaultContext())->firstId();
    }

    // Check the Pim
    public function checkPimID($productNumber = null)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productNumber',$productNumber));
        return $this->pimProductRepository->search($criteria, Context::createDefaultContext())->first();
    }

    // get all product object
    public function getProductData($productNumber = null,$context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productNumber',$productNumber));
        $criteria->addAssociation('crossSellings');
        return $this->productsRepository->search($criteria, Context::createDefaultContext())->first();
    }

    // Check the Product
    public function checkProductNumber($productNumber = null)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productNumber',$productNumber));
        return $this->productsRepository->search($criteria, Context::createDefaultContext())->first();
    }

    //delete all data
    public function deleteCrossSellData($productID = null,$crossSellingID = null,$context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('crossSellingId', $crossSellingID));
        $alldata = $this->productCrossSellingAssignedProductsRepository->searchIds($criteria,$context)->getIds();
        if($alldata){
            foreach($alldata as $crossid) {
                if ($crossid) {
                    $this->productCrossSellingAssignedProductsRepository->delete(
                        [
                            [
                                'id' => $crossid,
                            ]
                        ],
                        Context::createDefaultContext()
                    );
                }
            }
        }
        return true;
    }

    // Check the Product Cross Selling Related Items
    public function checkRelatedAvailability($productID = null,$crossSellingID = null,$relatedProductID = null,$context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productId', $relatedProductID));
        $criteria->addFilter(new EqualsFilter('crossSellingId', $crossSellingID));
        return $this->productCrossSellingAssignedProductsRepository->searchIds($criteria,$context)->getIds();
    }
}
