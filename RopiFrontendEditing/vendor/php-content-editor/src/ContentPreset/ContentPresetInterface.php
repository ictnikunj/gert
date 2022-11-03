<?php
namespace Ropi\ContentEditor\ContentPreset;

interface ContentPresetInterface
{

    public function getId(): ?string;

    public function getCreationTime(): ?\DateTimeInterface;

    public function setName(string $name): void;

    public function getName(): string;

    public function getType(): string;

    public function setStructure(array $structure): void;

    public function getStructure(): array;
}
