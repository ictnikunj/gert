<?php declare(strict_types=1);

namespace Ropi\FrontendEditing\ContentEditor\DocumentContext\Subscriber;

use Ropi\FrontendEditing\ContentEditor\DocumentContext\Events\DocumentContextBuildEvent;
use Ropi\FrontendEditing\ContentEditor\DocumentContext\Events\DocumentContextBuildUrlEvent;
use Shopware\Storefront\Page\Product\ProductPage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductIndexDocumentContextSubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents(): array
    {
        return [
            DocumentContextBuildEvent::getNameForControllerAction('ShopwareStorefront', 'Product', 'index') => 'onBuild',
            DocumentContextBuildUrlEvent::getNameForControllerAction('ShopwareStorefront', 'Product', 'index') => 'onBuildUrl'
        ];
    }

    public function onBuild(DocumentContextBuildEvent $event): void
    {
        $page = $event->getParameters()['page'] ?? null;
        if (!$page instanceof ProductPage) {
            return;
        }

        $productId = $page->getProduct()->getParentId();
        if (!$productId) {
            $productId = $page->getProduct()->getId();
        }

        $event->getDocumentContext()['subcontext'] = $productId;
    }

    public function onBuildUrl(DocumentContextBuildUrlEvent $event): void
    {
        $event->setRoute('frontend.detail.page');

        if (isset($event->getDocumentContext()['subcontext'])) {
            $subcontext = $event->getDocumentContext()['subcontext'];
            $event->setParameters(['productId' => $subcontext]);
        }
    }
}
