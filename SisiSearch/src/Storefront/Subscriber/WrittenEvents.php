<?php

declare(strict_types=1);

namespace Sisi\Search\Storefront\Subscriber;

use Elasticsearch\Client;
use Exception;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\Event\NestedEventCollection;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Sisi\Search\Service\ClientService;
use Sisi\Search\Service\ContextService;
use Sisi\Search\Service\InsertTimestampService;
use Sisi\Search\Service\TextService;
use Sisi\Search\Services\ElasticsearchService;
use Sisi\Search\ServicesInterfaces\InterfaceFrontendService;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Customer\CustomerEvents;

class WrittenEvents implements EventSubscriberInterface
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
     *
     * @var Logger
     */
    private $loggingService;




    /**
     * @param SystemConfigService $systemConfigService
     * @param Connection $connection
     * @param Logger $loggingService
     *
     */
    public function __construct(
        SystemConfigService $systemConfigService,
        Connection $connection,
        Logger $loggingService
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->connection = $connection;
        $this->loggingService = $loggingService;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            EntityWrittenContainerEvent::Class => 'onDelete',
        ];
    }

    /**
     * Event-function to add the ean item prop
     *
     * @param EntityWrittenEvent $event
     */

    public function onDelete(EntityWrittenContainerEvent $event): void
    {
        $clienthaendler = new ClientService();
        $config = $this->systemConfigService->get("SisiSearch.config");
        $strDeleteEvent = true;
        if (array_key_exists('strdeleteproduct', $config)) {
            if ($config['strdeleteproduct'] === 'yes') {
                $strDeleteEvent = false;
            }
        }
        if ($strDeleteEvent) {
            $client = $clienthaendler->createClient($config);
            $context = $event->getContext();
            $events = $event->getEvents();
            $delteResult = $this->getList($events);
            $elements = $events->getElements();
            $indexies = $this->findIndexies($this->connection, $context->getLanguageId());
            $this->deleteInaktivefromEsServer($elements, $indexies, $client);
            if (array_key_exists('product.deleted', $delteResult)) {
                foreach ($indexies as $index) {
                    foreach ($delteResult['product.deleted'] as $productId) {
                        $params = [
                            'index' => $index["index"],
                            'id' => $productId
                        ];
                        try {
                            $client->delete($params);
                        } catch (Exception $e) {
                            $this->loggingService->log(100, $e->getMessage());
                        }
                    }
                }
            }
        }
    }
    private function deleteInaktivefromEsServer(array $elements, array $indexies, Client $client): void
    {
        foreach ($elements as $element) {
            $payloads = $element->getPayloads();
            if ($payloads  != null) {
                foreach ($payloads as $payload) {
                    if (array_key_exists('active', $payload)) {
                        if ($payload['active'] === false) {
                            foreach ($indexies as $index) {
                                $params = [
                                    'index' => $index["index"],
                                    'id' => $payload['id']
                                ];
                                try {
                                    $client->delete($params);
                                } catch (Exception $e) {
                                    $this->loggingService->log(100, $e->getMessage());
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     *
     */
    public function getList(NestedEventCollection $events): array
    {
        $list = [];

        foreach ($events as $event) {
            if ($event instanceof EntityWrittenEvent) {
                $list[$event->getName()] = $event->getIds();
            } else {
                $list[] = $event;
            }
        }

        return $list;
    }
    /**
     * @param Connection $connection
     * @param string|null $shopId
     * @param string|null $language
     *
     * @return mixed
     */
    public function findIndexies(Connection $connection, string $language = null)
    {
        $handler = $connection->createQueryBuilder()
            ->select(['*, HEX(id), `time`,`index`'])
            ->from('s_plugin_sisi_search_es_index');


        if ($language != null) {
            $handler->andWhere('language=:language');
        }
        $handler->orderBy('s_plugin_sisi_search_es_index.time', 'desc');

        if ($language != null) {
            $handler->setParameter('language', $language);
        }
        return $handler->execute()->fetchAllAssociative();
    }
}
