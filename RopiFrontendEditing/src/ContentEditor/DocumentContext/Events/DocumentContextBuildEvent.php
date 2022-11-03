<?php declare(strict_types=1);

namespace Ropi\FrontendEditing\ContentEditor\DocumentContext\Events;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\GenericEvent;
use Shopware\Core\Framework\Event\NestedEvent;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

class DocumentContextBuildEvent extends NestedEvent implements GenericEvent
{
    /**
     * @var array
     */
    protected $documentContext;

    /**
     * @var array
     */
    protected $parameters;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var SalesChannelContext
     */
    protected $context;

    public function __construct(array $documentContext, array $parameters, Request $request, SalesChannelContext $context)
    {
        $this->documentContext = $documentContext;
        $this->parameters = $parameters;
        $this->request = $request;
        $this->context = $context;
    }

    public function getName(): string
    {
        return static::getNameForControllerAction(
            $this->documentContext['bundle'] ?? '',
            $this->documentContext['controller'] ?? '',
            $this->documentContext['action'] ?? ''
        );
    }

    public function setDocumentContext(array $documentContext): void
    {
        $this->documentContext = $documentContext;
    }

    public function &getDocumentContext(): array
    {
        return $this->documentContext;
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->context;
    }

    public function getContext(): Context
    {
        return $this->context->getContext();
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public static function getNameForControllerAction(
        string $bundleName,
        string $controllerName,
        string $actionName
    ): string
    {
        $eventName = 'ropi_frontend_editing.document_context.';

        $eventName .= implode('_', [
            $bundleName,
            $controllerName,
            $actionName
        ]);

        $eventName .= '.build';

        return mb_strtolower($eventName, 'UTF-8');
    }
}
