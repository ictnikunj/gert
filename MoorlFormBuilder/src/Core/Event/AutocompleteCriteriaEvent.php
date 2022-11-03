<?php declare(strict_types=1);

namespace MoorlFormBuilder\Core\Event;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Contracts\EventDispatcher\Event;

class AutocompleteCriteriaEvent extends Event
{
    private Context $context;
    private Criteria $criteria;
    private array $element;
    private ?string $action;

    public function __construct(
        Context $context,
        Criteria $criteria,
        array $element,
        ?string $action
    )
    {
        $this->context = $context;
        $this->criteria = $criteria;
        $this->element = $element;
        $this->action = $action;
    }

    /**
     * @return array
     */
    public function getElement(): array
    {
        return $this->element;
    }

    /**
     * @param array $element
     */
    public function setElement(array $element): void
    {
        $this->element = $element;
    }

    /**
     * @return string|null
     */
    public function getAction(): ?string
    {
        return $this->action;
    }

    /**
     * @param string|null $action
     */
    public function setAction(?string $action): void
    {
        $this->action = $action;
    }

    /**
     * @return Context
     */
    public function getContext(): Context
    {
        return $this->context;
    }

    /**
     * @param Context $context
     */
    public function setContext(Context $context): void
    {
        $this->context = $context;
    }

    /**
     * @return Criteria|null
     */
    public function getCriteria(): ?Criteria
    {
        return $this->criteria;
    }

    /**
     * @param Criteria|null $criteria
     */
    public function setCriteria(?Criteria $criteria): void
    {
        $this->criteria = $criteria;
    }

    public function getName(): string
    {
        return 'moorl_form_builder.form.autocomplete.criteria';
    }
}
