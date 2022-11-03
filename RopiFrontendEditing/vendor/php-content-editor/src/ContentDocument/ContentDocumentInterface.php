<?php
namespace Ropi\ContentEditor\ContentDocument;

interface ContentDocumentInterface
{

    public function getId(): ?string;

    public function getCreationTime(): ?\DateTimeInterface;

    public function setPublished(bool $published): void;

    public function getPublished(): bool;

    public function setUsername(string $username): void;

    public function getUsername(): string;

    public function setStructure(array $structure): void;

    public function getStructure(): array;
}
