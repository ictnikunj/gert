<?php declare(strict_types=1);

namespace Ropi\FrontendEditing\ContentEditor\Renderer;

use Psr\Container\ContainerInterface;
use Ropi\ContentEditor\Environment\ContentEditorEnvironmentInterface;
use Ropi\ContentEditor\Facade\Exception\RenderException;
use Ropi\ContentEditor\Renderer\ContentElementRendererInterface;
use Ropi\FrontendEditing\ContentEditor\Renderer\Events\ContentElementRenderEvent;
use Shopware\Core\Framework\Adapter\Twig\TemplateFinder;
use Shopware\Core\PlatformRequest;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class ContentElementRenderer implements ContentElementRendererInterface
{
    /**
     * @var ContentEditorEnvironmentInterface
     */
    protected $contentEditorEnvironment;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function __construct(
        ContentEditorEnvironmentInterface $contentEditorEnvironment,
        EventDispatcherInterface $eventDispatcher,
        ContainerInterface $container
    ) {
        $this->contentEditorEnvironment = $contentEditorEnvironment;
        $this->eventDispatcher = $eventDispatcher;
        $this->container = $container;
    }

    /**
     * @throws RenderException
     */
    public function render(string $elementType, array $elementNode): string
    {
        try {
            $view = $this->container->get(TemplateFinder::class)->find(
                $this->resolveContentElementView($elementType),
                false,
                null
            );

            /** @var $request Request */
            $request = $this->container->get('request_stack')->getCurrentRequest();
            $salesChannelContext = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);

            $contentElementRenderEvent = new ContentElementRenderEvent(
                $elementType,
                $view,
                [
                    'data' => $elementNode,
                    'breakpoints' => $this->contentEditorEnvironment->getBreakpoints()
                ],
                $request,
                $salesChannelContext
            );

            $this->eventDispatcher->dispatch($contentElementRenderEvent);
            $this->eventDispatcher->dispatch($contentElementRenderEvent, $contentElementRenderEvent->getName());

            return $this->container->get('twig')->render($view, $contentElementRenderEvent->getParameters());
        } catch (\Throwable $throwable) {
            if ($this->contentEditorEnvironment->editorOpened()) {
                return '<pre
                    style="word-break: break-all;
                    white-space: pre-line;
                    overflow: visible;
                    background-color: #ddd;
                    color: #c23;">' . $throwable->__toString() . '</pre>';
            }
        }

        return '';
    }

    /**
     * @throws RenderException
     */
    protected function resolveContentElementView(string $elementType): string
    {
        $segments = explode('/', $elementType, 2);
        if (count($segments) !== 2) {
            throw new RenderException(
                'Content type "' . $elementType . ' is invalid',
                1592001500
            );
        }

        list($bundle, $element) = $segments;

        return "@{$bundle}/content-editor/elements/{$element}/element.html.twig";
    }
}
