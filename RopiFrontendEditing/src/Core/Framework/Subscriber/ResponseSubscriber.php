<?php declare(strict_types=1);

namespace Ropi\FrontendEditing\Core\Framework\Subscriber;

use Shopware\Core\PlatformRequest;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ResponseSubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }

    public function onResponse(ResponseEvent $event): void
    {
        $event->getResponse()->headers->set(
            PlatformRequest::HEADER_FRAME_OPTIONS,
            'sameorigin',
            true
        );
    }
}
