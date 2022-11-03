<?php declare(strict_types=1);

namespace Ropi\FrontendEditing\ContentEditor\DocumentContext;

use Ropi\FrontendEditing\ContentEditor\DocumentContext\Events\DocumentContextBuildUrlEvent;
use Shopware\Core\Framework\Context;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\RouterInterface;

class DocumentContextUrlBuilder
{

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var RouterInterface
     */
    protected $router;

    public function __construct(EventDispatcherInterface $eventDispatcher, RouterInterface $router)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->router = $router;
    }

    public function build(array $documentContext, Context $context): string
    {
        $documentContextBuildRouteEvent = new DocumentContextBuildUrlEvent($documentContext, $context);

        $this->eventDispatcher->dispatch($documentContextBuildRouteEvent, $documentContextBuildRouteEvent->getName());

        if (!$documentContextBuildRouteEvent->getRoute()) {
            return '';
        }

        return $this->router->generate(
            $documentContextBuildRouteEvent->getRoute(),
            $documentContextBuildRouteEvent->getParameters(),
            RouterInterface::ABSOLUTE_URL
        );
    }
}
