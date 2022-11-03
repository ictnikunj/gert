<?php

declare(strict_types=1);

namespace Sisi\SisiEsContentSearch6\Storefront\Subscriber;

use Elasticsearch\ClientBuilder;
use Elasticsearch\Endpoints\Cat\Help;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Event\StorefrontRenderEvent;
use Shopware\Storefront\Page\Navigation\NavigationPageLoadedEvent;
use Shopware\Storefront\Page\Search\SearchPageLoadedEvent;
use Shopware\Storefront\Page\Suggest\SuggestPageLoadedEvent;
use Sisi\Search\ESIndexInterfaces\InterfaceCreateCriteria;
use Sisi\Search\Events\SisiSearchPageLoadedEvent;
use Sisi\Search\Service\ClientService;
use Sisi\Search\Service\ContextService;
use Sisi\Search\Service\SearchHelpService;
use Sisi\SisiEsContentSearch6\Service\HelpfunctionService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sisi\SisiEsContentSearch6\Service\SearchService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Sisi\Search\ESindexing\CreateCriteria;
use Doctrine\DBAL\Connection;
use Sisi\Search\Events\SisiSuggestPageLoadedEvent;

/**
 * @SuppressWarnings(PHPMD)
 */
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
     * @var InterfaceCreateCriteria
     */
    private $createCriteria;


    /**
     * @param SystemConfigService $systemConfigService
     * @param Connection $connection
     * @param ContainerInterface $container
     * @param InterfaceCreateCriteria $createCriteria
     *
     */
    public function __construct(
        SystemConfigService $systemConfigService,
        Connection $connection,
        ContainerInterface $container,
        InterfaceCreateCriteria $createCriteria
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->connection = $connection;
        $this->container = $container;
        $this->createCriteria = $createCriteria;
    }

    /**
     * {@inheritDoc}
     */

    public static function getSubscribedEvents(): array
    {

        return [
            SuggestPageLoadedEvent::class => 'onSuggestSearch',
            SisiSuggestPageLoadedEvent::class => 'onSuggestSearch',
            /**  @phpstan-ignore-next-line **/
            SisiSearchPageLoadedEvent::class => 'onSearchPage',
            SearchPageLoadedEvent::class => 'onSearchPage',
            NavigationPageLoadedEvent::class => 'onContent'
        ];
    }

    /**
     * Event-function to add the ean item prop
     *
     * @param SuggestPageLoadedEvent| SisiSuggestPageLoadedEvent $event
     */

    public function onSuggestSearch($event): void
    {
        $page = $event->getPage();
        $term = $page->getSearchTerm();
        $this->search($event, $term);
    }

    /**
     * Event-function to add the ean item prop
     *
     * @param SearchPageLoadedEvent | SisiSearchPageLoadedEvent | SuggestPageLoadedEvent | SisiSuggestPageLoadedEvent $event
     *
     *  @phpstan-ignore-next-line
     */
    public function onSearchPage($event): void
    {
        $config = $this->systemConfigService->get("SisiEsContentSearch6.config");
        if (array_key_exists('searchPage', $config)) {
            if ($config['searchPage'] === '1') {
                $page = $event->getPage();
                $term = $page->getSearchTerm();
                $this->search($event, $term);
            }
        }
    }

    /**
     * Event-function to add the ean item prop
     *
     * @param NavigationPageLoadedEvent $event
     *
     *  @phpstan-ignore-next-line
     */
    public function onContent($event): void
    {
        $request = $event->getRequest();
        $term = $request->get('search');
        $categorieId = null;
        $categorie = $request->attributes->all();
        if (array_key_exists('navigationId', $categorie)) {
            $categorieId = $categorie['navigationId'];
        }
        $this->search($event, $term, true, $categorieId);
    }

    /**
     * Event-function to add the ean item prop
     *
     * @param SearchPageLoadedEvent | SisiSearchPageLoadedEvent: $event
     * @param string|null $term
     *
     *  @phpstan-ignore-next-line
     **/
    private function search($event, string $term = null, $str = false, $categorieId = null): void
    {
        $heandlerSearch = new SearchService();
        $heandlerSearch->search($event, $this->systemConfigService, $this->connection, $term, $str, $categorieId);
    }
}
