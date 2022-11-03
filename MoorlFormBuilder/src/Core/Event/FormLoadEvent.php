<?php declare(strict_types=1);

namespace MoorlFormBuilder\Core\Event;

use MoorlFormBuilder\Core\Content\Form\FormEntity;
use Shopware\Core\Framework\Context;
use Symfony\Contracts\EventDispatcher\Event;

class FormLoadEvent extends Event
{
    private Context $context;
    private ?FormEntity $form = null;

    public function __construct(
        Context $context,
        FormEntity $form
    )
    {
        $this->context = $context;
        $this->form = $form;
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
     * @return FormEntity|null
     */
    public function getForm(): ?FormEntity
    {
        return $this->form;
    }

    /**
     * @param FormEntity|null $form
     */
    public function setForm(?FormEntity $form): void
    {
        $this->form = $form;
    }

    public function getName(): string
    {
        return 'moorl_form_builder.form.load';
    }
}
