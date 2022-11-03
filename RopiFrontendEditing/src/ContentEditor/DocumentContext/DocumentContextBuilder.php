<?php declare(strict_types=1);

namespace Ropi\FrontendEditing\ContentEditor\DocumentContext;

use Ropi\ContentEditor\Environment\ContentEditorEnvironmentInterface;
use Ropi\FrontendEditing\ContentEditor\DocumentContext\Events\DocumentContextBuildEvent;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class DocumentContextBuilder
{

    /**
     * @var ContentEditorEnvironmentInterface
     */
    protected $contentEditorEnvironment;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function __construct(ContentEditorEnvironmentInterface $contentEditorEnvironment, EventDispatcherInterface $eventDispatcher)
    {
        $this->contentEditorEnvironment = $contentEditorEnvironment;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function build(Request $request, SalesChannelContext $salesChannelContext, array $parameters = []): array
    {
        $controllerInfo = $this->resolveControllerInfo($request);

        $documentContext = [
            'salesChannelId' => $salesChannelContext->getSalesChannel()->getId(),
            'bundle' => $controllerInfo->vendorName . $controllerInfo->packageName,
            'controller' => $controllerInfo->controllerName,
            'action' => $controllerInfo->actionName,
            'languageId' => $salesChannelContext->getSalesChannel()->getLanguageId()
        ];

        $documentContextBuildEvent = new DocumentContextBuildEvent(
            $documentContext,
            $parameters,
            $request,
            $salesChannelContext
        );

        $this->eventDispatcher->dispatch($documentContextBuildEvent);
        $this->eventDispatcher->dispatch($documentContextBuildEvent, $documentContextBuildEvent->getName());

        return $documentContextBuildEvent->getDocumentContext();
    }

    protected function resolveControllerInfo(Request $request): object
    {
        $controllerInfo = (object) [
            'vendorName' => '',
            'packageName' => '',
            'controllerName' => '',
            'actionName' => '',
        ];

        $controller = $request->attributes->get('_controller');

        if (!$controller || strpos($controller, '::') === false) {
            return $controllerInfo;
        }

        list($controllerFqcn, $actionName) = explode('::', $controller, 2);
        $controllerSegments = explode('\\', $controllerFqcn);
        if (count($controllerSegments) < 3) {
            return $controllerInfo;
        }

        $controllerInfo->vendorName = array_shift($controllerSegments);
        $controllerInfo->packageName = array_shift($controllerSegments);

        $controllerName = array_pop($controllerSegments);
        if (strlen($controllerName) > 10) {
            $controllerName = substr($controllerName, 0, -10);
        }

        $controllerInfo->controllerName = $controllerName;
        $controllerInfo->actionName = $actionName;

        return $controllerInfo;
    }
}
