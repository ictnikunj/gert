<?php declare(strict_types=1);

namespace Ropi\FrontendEditing\ContentEditor\Renderer\Subscriber;

use Ropi\FrontendEditing\ContentEditor\Renderer\Events\ContentElementRenderEvent;
use Ropi\FrontendEditing\ContentEditor\Renderer\ClassBuilder\ClassBuilderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ColumnsElementRenderSubscriber implements EventSubscriberInterface
{

    /**
     * @var ClassBuilderInterface
     */
    private $classBuilder;

    public function __construct(ClassBuilderInterface $classBuilder)
    {
        $this->classBuilder = $classBuilder;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ContentElementRenderEvent::getNameForContentElementType('RopiFrontendEditing/columns') => 'onRender'
        ];
    }

    public function onRender(ContentElementRenderEvent $event): void
    {
        $columnsConfig = $event->getParameters()['data']['configuration']['columns'] ?? [];

        $event->setParameter('colClasses', $this->classBuilder->buildColumnClasses($columnsConfig));
    }
}
