<?php declare(strict_types=1);

namespace MoorlFormBuilder\Core\Event;

use Symfony\Contracts\EventDispatcher\Event;

class FormEvent extends FormFireEvent
{
    public function getName(): string
    {
        return 'moorl_form_builder.action.form';
    }
}
