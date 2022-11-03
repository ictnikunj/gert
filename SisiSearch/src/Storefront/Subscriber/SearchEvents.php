<?php

declare(strict_types=1);

namespace Sisi\Search\Storefront\Subscriber;

use _HumbugBox3ab8cff0fda0\VARIANT;
use phpDocumentor\Reflection\Types\Boolean;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Event\StorefrontRenderEvent;
use Shopware\Storefront\Page\Search\SearchPageLoadedEvent;
use Shopware\Storefront\Page\Suggest\SuggestPageLoadedEvent;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Sisi\Search\ESIndexInterfaces\InterfaceCreateCriteria;
use Sisi\Search\Service\FrontendService;
use Sisi\Search\Service\SearchCategorieService;
use Sisi\Search\Service\SearchEventService;
use Sisi\Search\ServicesInterfaces\InterfaceFrontendService;
use Sisi\Search\ServicesInterfaces\InterfaceSearchCategorieService;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sisi\Search\Components\CategoryService;
use Sisi\Search\Components\ManufactoryService;
use Sisi\Search\Service\SearchService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Sisi\Search\ESindexing\CreateCriteria;
use Doctrine\DBAL\Connection;
use Sisi\Search\Service\ProductService;
use Shopware\Core\Content\Product\SalesChannel\Listing\ProductListingResult;
use Sisi\Search\Service\SortingService;
use Shopware\Core\Content\Product\SalesChannel;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class SearchEvents implements EventSubscriberInterface
{

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;


    /**
     * @var Connection
     */
    private $connection;


    /**
     * @var ContainerInterface
     */
    private $container;


    /**
     * @var searchService
     */
    private $searchService;


    /**
     * @var  InterfaceCreateCriteria
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
     * @var InterfaceSearchCategorieService
     */
    protected $searchCategorieService;


    /**
     * @param SystemConfigService $systemConfigService
     * @param Connection $connection
     * @param ContainerInterface $container
     * @param InterfaceCreateCriteria $createCriteria
     * @param Logger $loggingService
     * @param InterfaceFrontendService $frontendService
     * @param InterfaceSearchCategorieService $searchCategorieSer
     *
     */
    public function __construct(
        SystemConfigService $systemConfigService,
        Connection $connection,
        ContainerInterface $container,
        InterfaceCreateCriteria $createCriteria,
        Logger $loggingService,
        InterfaceFrontendService $frontendService,
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
    }

    /**
     * {@inheritDoc}
     */

    public static function getSubscribedEvents(): array
    {
        return [
            SuggestPageLoadedEvent::class => 'onSuggestSearch',
            SearchPageLoadedEvent::class => 'onSearch'
        ];
    }

    /**
     * Event-function to add the ean item prop
     *
     * @param SearchPageLoadedEvent $event
     */
    public function onSearch(SearchPageLoadedEvent $event): void
    {
        $page = $event->getPage();
        $saleschannelContext = $event->getSalesChannelContext();
        $request = $event->getRequest();
        $systemConfig = $this->systemConfigService->get("SisiSearch.config", $saleschannelContext->getSalesChannel()->getId());
        $heandler = new SearchEventService($this->connection, $systemConfig, $this->container);
        $heandler->onSearch(
            $page,
            $this->createCriteria,
            $this->container,
            $this->searchService,
            $this->frontendService,
            $saleschannelContext,
            $request,
            $systemConfig
        );
    }

    /**
     * Event-function to add the ean item prop
     *
     * @param SuggestPageLoadedEvent $event
     */
    public function onSuggestSearch(SuggestPageLoadedEvent $event): void
    {
        $page = $event->getPage();
        $saleschannelContext = $event->getSalesChannelContext();
        $systemConfig = $this->systemConfigService->get("SisiSearch.config", $saleschannelContext->getSalesChannel()->getId());
        $heandler = new SearchEventService($this->connection, $systemConfig, $this->container);
        $request = $event->getRequest();
        $heandler->onSuggestSearch($page, $this->searchService, $this->frontendService, $saleschannelContext, $request, $systemConfig);
    }
}
