<?php declare(strict_types=1);

namespace Ropi\FrontendEditing\ContentEditor\Renderer\Subscriber;

use Ropi\ContentEditor\Environment\ContentEditorEnvironmentInterface;
use Ropi\FrontendEditing\ContentEditor\Renderer\ClassBuilder\ClassBuilderInterface;
use Ropi\FrontendEditing\ContentEditor\Renderer\Events\ContentElementRenderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ContentElementRenderSubscriber implements EventSubscriberInterface
{

    /**
     * @var ContentEditorEnvironmentInterface
     */
    private $contentEditorEnvironment;

    /**
     * @var ClassBuilderInterface
     */
    private $classBuilder;

    public function __construct(
        ContentEditorEnvironmentInterface $contentEditorEnvironment,
        ClassBuilderInterface $classBuilder
    ) {
        $this->contentEditorEnvironment = $contentEditorEnvironment;
        $this->classBuilder = $classBuilder;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ContentElementRenderEvent::class => 'onRender'
        ];
    }

    public function onRender(ContentElementRenderEvent $event): void
    {
        $event->setParameter('ropiFrontendEditingEditorOpened', $this->contentEditorEnvironment->editorOpened());

        $paddingConfig = $event->getParameters()['data']['configuration']['padding'] ?? null;
        if (is_array($paddingConfig)) {
            $event->setParameter('paddingClasses', $this->classBuilder->buildPaddingClasses($paddingConfig));
        }
    }
}
