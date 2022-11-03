<?php

declare(strict_types=1);

namespace Sisi\Search\Controller;

use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Product\SalesChannel\Listing\ProductListingResult;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Framework\Cache\Annotation\HttpCache;
use Sisi\Search\ESindexing\CreateCriteria;
use Sisi\Search\ESIndexInterfaces\InterfaceCreateCriteria;
use Sisi\Search\ESIndexInterfaces\InterSearchAjaxService;
use Sisi\Search\Events\SisiSearchPageLoadedEvent;
use Sisi\Search\Events\SisiSuggestPageLoadedEvent;
use Sisi\Search\Service\ContextService;
use Sisi\Search\Service\ExtSearchService;
use Sisi\Search\Service\FrontendService;
use Sisi\Search\Service\ProductService;
use Sisi\Search\Service\SearchCategorieService;
use Sisi\Search\Service\SearchEventService;
use Sisi\Search\Service\SearchHelpService;
use Sisi\Search\Service\SearchService;
use Sisi\Search\Service\SortingService;
use Sisi\Search\ServicesInterfaces\InterfaceFrontendService;
use Sisi\Search\ServicesInterfaces\InterfaceSearchCategorieService;
use Sisi\Search\Storefront\Page\SearchPageLoader;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sisi\Search\ESindexing\SearchAjaxService;

/**
 * @RouteScope(scopes={"storefront"})
 * @SuppressWarnings(PHPMD)
 */
class SearchController extends StorefrontController
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var searchService
     */
    private $searchService;

    /**
     * @var InterfaceCreateCriteria
     */
    private $createCriteria;

    /**
     *
     * @var Logger
     */
    private $loggingService;

    /**
     *
     * @var InterfaceFrontendService
     */
    private $frontendService;

    /**
     * @var SearchPageLoader
     */
    private $loader;

    /**
     * @var SearchAjaxService
     */
    private $searchajax;

    /**
     * @var InterfaceSearchCategorieService
     */
    protected $searchCategorieService;

    /**
     * SearchController constructor.
     * @param SystemConfigService $systemConfigService
     * @param Connection $connection
     * @param ContainerInterface $container
     * @param InterfaceCreateCriteria $createCriteria
     * @param Logger $loggingService
     * @param InterfaceFrontendService $frontendService
     * @param SearchPageLoader $loader
     * @param SearchPageLoader $loader
     * @param EventDispatcherInterface $eventDispatcher
     * @param InterSearchAjaxService $searchajax
     * @param InterfaceSearchCategorieService $searchCategorieServic
     */
    public function __construct(
        SystemConfigService $systemConfigService,
        Connection $connection,
        ContainerInterface $container,
        InterfaceCreateCriteria $createCriteria,
        Logger $loggingService,
        InterfaceFrontendService $frontendService,
        SearchPageLoader $loader,
        EventDispatcherInterface $eventDispatcher,
        InterSearchAjaxService $searchajax,
        InterfaceSearchCategorieService $searchCategorieService
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->connection = $connection;
        $this->container = $container;
        $this->loggingService = $loggingService;
        $this->frontendService = $frontendService;
        $this->searchCategorieService = $searchCategorieService;
        $this->searchService = new SearchService(
            $systemConfigService,
            $connection,
            $container,
            $loggingService,
            $searchCategorieService
        );
        $this->createCriteria = $createCriteria;
        $this->loader = $loader;
        $this->eventDispatcher = $eventDispatcher;
        $this->searchajax = $searchajax;
    }

    /**
     * @HttpCache()
     * @Route("/onsuggest", name="frontend.search.onsuggest", methods={"GET"}, defaults={"XmlHttpRequest"=true})
     * @param SalesChannelContext $context
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function onSuggest(SalesChannelContext $context, Request $request)
    {
        $page = $this->loader->loadSuggest($request, $context);
        $systemConfig = $this->systemConfigService->get("SisiSearch.config", $context->getSalesChannel()->getId());
        $heandler = new SearchEventService($this->connection, $systemConfig, $this->container);
        $heandler->onSuggestSearch($page, $this->searchService, $this->frontendService, $context, $request, $systemConfig);

        $this->eventDispatcher->dispatch(
            new SisiSuggestPageLoadedEvent($page, $context, $request)
        );
        $pfad = "@Storefront/storefront/layout/header/search-suggest-es.html.twig";
        if (array_key_exists('themeES', $systemConfig)) {
            if (!empty($systemConfig['themeES'])) {
                $pfad = $systemConfig['themeES'];
            }
        }

        return $this->renderStorefront(
            $pfad,
            ['page' => $page]
        );
    }

    /**
     * @HttpCache()
     * @Route("/onsearch", name="frontend.search.onsearch", methods={"GET"}, defaults={"XmlHttpRequest"=true})
     *
     * @param SalesChannelContext $context
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function onSearch(SalesChannelContext $context, Request $request)
    {
        $page = $this->loader->load($request, $context);
        $systemConfig = $this->systemConfigService->get("SisiSearch.config", $context->getSalesChannel()->getId());
        $heandler = new SearchEventService($this->connection, $systemConfig, $this->container);
        $heandler->onSearch(
            $page,
            $this->createCriteria,
            $this->container,
            $this->searchService,
            $this->frontendService,
            $context,
            $request,
            $systemConfig
        );
        $this->eventDispatcher->dispatch(
            new SisiSearchPageLoadedEvent($page, $context, $request)
        );

        return $this->renderStorefront('@Storefront/storefront/page/search/index.html.twig', ['page' => $page]);
    }

    /**
     * @HttpCache()
     * @Route("/onorder", name="frontend.search.onorder", methods={"GET"}, defaults={"XmlHttpRequest"=true})
     *
     * @param SalesChannelContext $context
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function onOrder(SalesChannelContext $context, Request $request)
    {
        $properties = $request->get('pro');
        $term = $request->query->get('search');
        $pageId = $request->query->get('p');
        $manufactoryIds = $request->query->get('ma');
        $elasticsearchAktive = true;
        $page = $this->loader->load($request, $context);
        $size = 10;
        $systemConfig = $this->systemConfigService->get("SisiSearch.config", $context->getSalesChannel()->getId());
        $hits = $systemConfig['producthitsSearch'];
        $criteria = new Criteria();
        $criteria->addAssociation('properties');
        $criteria->addAssociation('properties.group');
        $helpService = new SearchHelpService();
        $poductservice = new ProductService();
        $striphandler = new ExtSearchService();
        $productService = $this->container->get('sales_channel.product.repository');
        if (array_key_exists('producthits', $systemConfig)) {
            $size = (int)$systemConfig['producthitsSearch'];
        }
        $from = $helpService->getFromvalue($size, $pageId);
        $getParams['from'] = $from;
        $getParams['size'] = $size;
        if (empty($properties) && empty($manufactoryIds)) {
            $saleschannel = $context->getSalesChannel();
            $languageId = $saleschannel->getLanguageId();
            $newResult = $this->searchService->searchProducts(
                $term,
                $systemConfig,
                $pageId,
                $languageId,
                $saleschannel,
                $context,
                $this->frontendService,
                $this->container
            );
        } else {
            $term = $striphandler->stripUrl($term, $systemConfig);
            $getParams['pro'] = $properties;
            $getParams['ma'] = $manufactoryIds;
            $newResult = $this->searchajax->searchProducts(
                $term,
                $properties,
                $manufactoryIds,
                $systemConfig,
                $context,
                $this->connection,
                $getParams,
                $this->container
            );
        }
        $entities = null;
        $properties = null;
        $manufactories = null;
        if (!empty($newResult['hits']['hits'])) {
            $criteria = $poductservice->searchProducte($criteria, $newResult['hits']['hits']);
            $entities = $productService->search($criteria, $context);
            $sortservice = new SortingService();
            $sortservice->sortDbQueryToES($entities, $newResult['hits']['hits']);
            $properties = $sortservice->getProptertiesfilters($entities, $this->container, $systemConfig);
            $manufactories = $sortservice->getManufactory($entities, $this->container, $systemConfig);
        }
        $this->eventDispatcher->dispatch(
            new SisiSearchPageLoadedEvent($page, $context, $request)
        );
        $page->assign(
            [
                'sisi_elasticsearchResults' => $entities,
                'sisi_properties' => $properties,
                'sisi_manufactories' => $manufactories,
                'sisi_elasticsearchAktive' => $elasticsearchAktive,
                'sisi_search_hits' => $hits,
                'ESorginalResult' => $newResult,
                'pageindex' => $pageId,
                'cre' => $criteria,
                'sisi_sytemconfig' => $systemConfig
            ]
        );
        return $this->renderStorefront('@Storefront/storefront/page/search/index.html.twig', ['page' => $page]);
    }
}
