<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Storefront\Subscriber;

use Acris\ProductDownloads\Components\ProductDownloadService;
use Acris\ProductDownloads\Custom\ProductDownloadCollection;
use Shopware\Core\Content\Product\Events\ProductGatewayCriteriaEvent;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Product\ProductPageCriteriaEvent;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CartSubscriber implements EventSubscriberInterface
{
    private ProductDownloadService $productDownloadService;
    private SystemConfigService $configService;

    public function __construct(ProductDownloadService $productDownloadService, SystemConfigService $configService)
    {
        $this->productDownloadService = $productDownloadService;
        $this->configService = $configService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductGatewayCriteriaEvent::class => 'onProductGatewayCriteria'
        ];
    }

    public function onProductGatewayCriteria(ProductGatewayCriteriaEvent $event): void
    {
        $loadActive = $this->configService->get('AcrisProductDownloadsCS.config.loadDataCheckout');
        if(!empty($loadActive) && $loadActive === "load") {
            $this->productDownloadService->addProductAssociationCriteria($event->getCriteria());
        }

        $linkLoadActive = $this->configService->get('AcrisProductDownloadsCS.config.linkLoadDataCheckout');
        if(!empty($linkLoadActive) && $linkLoadActive === "load") {
            $this->productDownloadService->addProductLinkAssociationCriteria($event->getCriteria());
        }
    }
}
