<?php declare(strict_types=1);

namespace MoorlFormBuilder\Core\Content\FormElement;

interface FormElementInterface
{
    public function getType(): string;
    public function getMediaType(): string;
    public function getBehaviourClass(): string;
    public function getName(): ?string;
    public function getValue();
    public function setValue($value = null): void;
    public function getImageClass(): string;
}