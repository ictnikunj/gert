<?php declare(strict_types=1);

namespace Ropi\FrontendEditing\ContentEditor\Renderer\Events;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\GenericEvent;
use Shopware\Core\Framework\Event\NestedEvent;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

class ContentElementRenderEvent extends NestedEvent implements GenericEvent
{
    /**
     * @var string
     */
    protected $contentElementType;

    /**
     * @var string
     */
    protected $view;

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

    public function __construct(string $contentElementType, string $view, array $parameters, Request $request, SalesChannelContext $context)
    {
        $this->contentElementType = $contentElementType;
        $this->view = $view;
        $this->parameters = array_merge(['context' => $context], $parameters);
        $this->request = $request;
        $this->context = $context;
    }

    public function getContentElementType(): string
    {
        return $this->contentElementType;
    }

    public function getName(): string
    {
        return static::getNameForContentElementType($this->contentElementType);
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->context;
    }

    public function getContext(): Context
    {
        return $this->context->getContext();
    }

    public function getView(): string
    {
        return $this->view;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setParameter(string $key, $value): void
    {
        $this->parameters[$key] = $value;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public static function getNameForContentElementType(string $contentElementType): string
    {
        $eventName = 'ropi_frontend_editing.content_element.';
        $eventName .= str_replace('/', '.', $contentElementType);
        $eventName .= '.render';

        return mb_strtolower($eventName, 'UTF-8');
    }
}
