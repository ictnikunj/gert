<?php declare(strict_types=1);

namespace Ropi\FrontendEditing\ContentEditor\DocumentContext\Events;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\GenericEvent;
use Shopware\Core\Framework\Event\NestedEvent;

class DocumentContextBuildUrlEvent extends NestedEvent implements GenericEvent
{
    /**
     * @var array
     */
    protected $documentContext;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var string
     */
    protected $route = '';

    /**
     * @var array
     */
    protected $parameters = [];

    public function __construct(array $documentContext, Context $context)
    {
        $this->documentContext = $documentContext;
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

    public function getDocumentContext(): array
    {
        return $this->documentContext;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function setRoute(string $route): void
    {
        $this->route = $route;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    public function &getParameters(): array
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

        $eventName .= '.build_route';

        return mb_strtolower($eventName, 'UTF-8');
    }
}
