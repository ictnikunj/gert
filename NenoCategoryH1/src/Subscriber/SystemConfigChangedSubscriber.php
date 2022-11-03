<?php

namespace Neno\CategoryH1\Subscriber;

use Shopware\Core\Framework\Adapter\Cache\CacheClearer;
use Shopware\Core\System\SystemConfig\Event\SystemConfigChangedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SystemConfigChangedSubscriber implements EventSubscriberInterface {

    private CacheClearer $cacheClearer;

    public function __construct
    (
        CacheClearer $cacheClearer
    ) {
        $this->cacheClearer = $cacheClearer;
    }

    static string $PLUGIN_NAME = 'NenoCategoryH1';

    public static function getSubscribedEvents(): array
    {
        return [
            SystemConfigChangedEvent::class => 'onConfigChanged'
        ];
    }

    private function shouldHandle(string $key): bool {
        return self::$PLUGIN_NAME . '.config.zzzNenoCacheClear' === $key;
    }

    public function onConfigChanged(SystemConfigChangedEvent $event):void {
        if ($this->shouldHandle($event->getKey())) {
            $this->cacheClearer->clear();
        }
    }
}
