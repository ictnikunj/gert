<?php declare(strict_types=1);

namespace Ropi\FrontendEditing\ContentEditor\DocumentContext\Subscriber;

use Ropi\FrontendEditing\ContentEditor\DocumentContext\Events\DocumentContextBuildEvent;
use Ropi\FrontendEditing\ContentEditor\DocumentContext\Events\DocumentContextBuildUrlEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NavigationIndexDocumentContextSubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents(): array
    {
        return [
            DocumentContextBuildEvent::getNameForControllerAction('ShopwareStorefront', 'Navigation', 'index') => 'onBuild',
            DocumentContextBuildUrlEvent::getNameForControllerAction('ShopwareStorefront', 'Navigation', 'index') => 'onBuildUrl'
        ];
    }

    public function onBuild(DocumentContextBuildEvent $event): void
    {
        $navigationId = $event->getRequest()->get(
            'navigationId',
            $event->getSalesChannelContext()->getSalesChannel()->getNavigationCategoryId()
        );

        $event->getDocumentContext()['subcontext'] = $navigationId;
    }

    public function onBuildUrl(DocumentContextBuildUrlEvent $event): void
    {
        $event->setRoute('frontend.navigation.page');

        if (isset($event->getDocumentContext()['subcontext'])) {
            $subcontext = $event->getDocumentContext()['subcontext'];
            $event->setParameters(['navigationId' => $subcontext]);
        }
    }
}
