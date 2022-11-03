<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Test\Mock;

use Shopware\Core\Framework\Event\GenericEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EventDispatcherMock implements EventDispatcherInterface
{
    /**
     * @var object[]
     */
    private $sentEvents = [];

    public function getSentEvents(): array
    {
        return $this->sentEvents;
    }

    /**
     * @param object $event
     */
    public function dispatch($event, ?string $eventName = null): object
    {
        if ($eventName === null && $event instanceof GenericEvent) {
            $eventName = $event->getName();
        }

        $this->sentEvents[$eventName] = $event;

        return $event;
    }
}
