<?php declare(strict_types=1);

namespace MoorlFormBuilder\Core\Content\Product\Subscriber;

use MoorlFormBuilder\Core\Service\FbService;
use Shopware\Core\Content\Product\Events\ProductGatewayCriteriaEvent;
use Shopware\Core\Content\Product\Events\ProductListingCriteriaEvent;
use Shopware\Core\Content\Product\Events\ProductSearchCriteriaEvent;
use Shopware\Core\Content\Product\Events\ProductSuggestCriteriaEvent;
use Shopware\Core\Framework\Event\ShopwareSalesChannelEvent;
use Shopware\Storefront\Page\Product\ProductPageCriteriaEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductCriteriaSubscriber implements EventSubscriberInterface
{
    private FbService $service;

    public function __construct(
        FbService $service
    )
    {
        $this->service = $service;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductGatewayCriteriaEvent::class => 'onCriteriaEvent',
            ProductListingCriteriaEvent::class => 'onCriteriaEvent',
            ProductPageCriteriaEvent::class => 'onCriteriaEvent',
            ProductSearchCriteriaEvent::class => 'onCriteriaEvent',
            ProductSuggestCriteriaEvent::class => 'onCriteriaEvent'
        ];
    }

    public function onCriteriaEvent(ShopwareSalesChannelEvent $event): void
    {
        $this->service->enrichSalesChannelProductCriteria(
            $event->getCriteria(),
            $event->getSalesChannelContext()->getSalesChannelId()
        );
    }
}
