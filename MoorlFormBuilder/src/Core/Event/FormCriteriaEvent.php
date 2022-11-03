<?php declare(strict_types=1);

namespace MoorlFormBuilder\Core\Event;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Contracts\EventDispatcher\Event;

class FormCriteriaEvent extends Event
{
    private Context $context;
    private Criteria $criteria;

    public function __construct(
        Context $context,
        Criteria $criteria
    )
    {
        $this->context = $context;
        $this->criteria = $criteria;
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
        return 'moorl_form_builder.form.criteria';
    }
}
