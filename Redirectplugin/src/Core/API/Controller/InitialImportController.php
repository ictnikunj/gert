<?php declare(strict_types=1);

namespace Redirectplugin\Core\API\Controller;
use GuzzleHttp\Exception\RequestException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\Framework\Context;
use GuzzleHttp\Client;
use Shopware\Core\Defaults;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
/**
 * @RouteScope(scopes={"api"})
 */
class InitialImportController extends AbstractController
{
    /**
     * @var AbstractSalesChannelContextFactory
     */
    private $contextFactory;

    /**
     * @var EntityRepositoryInterface
     */
    private $productRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $redirecterRepository;

    /**
     * @var Client
     */
    private $restClient;

    public function __construct(
        AbstractSalesChannelContextFactory $contextFactory,
        EntityRepositoryInterface $productRepository,
        EntityRepositoryInterface $redirecterRepository
    ){
        $this->contextFactory = $contextFactory;
        $this->productRepository = $productRepository;
        $this->redirecterRepository = $redirecterRepository;
        $this->restClient = new Client();
    }
    /**
     * @Route("/api/url/initialimport", name="api.action.price.initialimport", methods={"POST"})
     */
    public function initialimport(Context $context,Request $request): JsonResponse
    {
        $languageid = $request->get('languageid');
        if($languageid !== null) {
            try {
                $response = $this->restClient->post('https://www.wisa-sanitair.com/api/oauth/token', [
                    'form_params' => [
                        'grant_type' => 'client_credentials',
                        'client_id' => 'SWIATVFKYZHPEUHFEDHUAHPVZQ',
                        'client_secret' => 'M0pMUmZhd1YwemVPeTN4a2dsMVVVS3dyaFdHR0hSSXJGYUl6MzM',
                    ]
                ]);
                if ($response->getStatusCode()) {
                    if ($response->getStatusCode() == 200) {
                        $response_data = $response->getBody()->getContents();
                        $token_show = json_decode($response_data, true);
                        $token = $token_show['access_token'];
                        try {
                            $responseProducts = $this->restClient->post('https://www.wisa-sanitair.com/api/search/product', [
                                'headers' => [
                                    'Authorization' => 'Bearer ' . $token,
                                    "Accept" => "application/json",
                                    "sw-language-id" => $languageid
                                ],
                            ]);
                            $responseProductsdata = $responseProducts->getBody()->getContents();
                            $responseProductsdata = json_decode($responseProductsdata, true);

                            if($languageid == '2fbb5fe2e29a4d70aa5854ce7ce3e20b'){
                                $languagePath = 'nl/';
                                $liveLanguagePath = '';

                            }else if($languageid == '39172b837d8d49349e7326e8239a67de'){
                                $languagePath = 'es/';
                                $liveLanguagePath = 'es/';

                            }else if($languageid == '9df70900758c4e2da6bfc8613160aa01'){
                                $languagePath = 'de/';
                                $liveLanguagePath = 'de/';

                            }else if($languageid == 'f6287ca96ad9492db32f18a618ec78e1'){
                                $languagePath = 'en/';
                                $liveLanguagePath = 'en/';
                            }
                            $i = 0;
                            foreach ($responseProductsdata['data'] as $responseProduct) {

                                $productNumber = $responseProduct['productNumber'];
                                $key = null;
                                if (isset($responseProduct['customFields'])) {
                                    foreach (array_keys($responseProduct['customFields']) as $k) {
                                        if (preg_match('/^migration_attribute_(\\d+)_url_path_98$/', $k, $matches)) {
                                            $key = $k;
                                        }
                                    }
                                    if ($key !== null) {
                                        $url = $languagePath.$responseProduct['customFields'][$key];

                                        if (!empty($url)) {
                                            $criteria = new Criteria();
                                            $criteria->addFilter(new EqualsFilter('sourceURL', $url));
                                            $count = $this->redirecterRepository->search($criteria, $context)->count();
                                            if ($count == 0) {

                                                $salesChannelContext = $this->contextFactory->create(
                                                    Uuid::randomHex(),
                                                    Defaults::SALES_CHANNEL,
                                                    [SalesChannelContextService::LANGUAGE_ID => $languageid]
                                                );

                                                $criteria = new Criteria();
                                                $criteria->getAssociation('seoUrls');
                                                $criteria->addFilter(new EqualsFilter('productNumber', $productNumber));
                                                $ProductData = $this->productRepository->search($criteria, $salesChannelContext->getContext());

                                                if ($ProductData->getTotal() > 0) {

                                                    foreach ($ProductData->getEntities()->getElements() as $data) {
                                                        $newUrl = null;
                                                        foreach ($data->getSeoUrls()->getElements() as $seodata) {
                                                            if ($newUrl == null && $seodata->getlanguageId() == $languageid) {
                                                                $newUrl = $liveLanguagePath.$seodata->getseoPathInfo();
                                                                break;
                                                            }
                                                        }
                                                        if(($newUrl && $url) && ($newUrl != $url)) {
                                                            $store = [
                                                                'id' => Uuid::randomHex(),
                                                                'sourceURL' => $url,
                                                                'targetURL' => $newUrl,
                                                                'httpCode' => 302
                                                            ];
                                                            $this->redirecterRepository->upsert([$store], $context);
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                $i++;
                                if($i == 5){
                                    die;
                                }
                            }

                        } catch (RequestException $e) {
                            return new JsonResponse(['type' => 'Success', 'message' => null]);
                        }
                    }
                }
            } catch (RequestException $e) {
                return new JsonResponse(['type' => 'Success', 'message' => null]);
            }
        }
        return new JsonResponse(['type'=> 'Success' , 'message' => null]);
    }
}
