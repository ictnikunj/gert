<?php
namespace Ropi\ContentEditor\ContentDocument;

trait ContentDocumentTrait
{

    /**
     * @var string|null
     */
    protected $id;

    /**
     * @var string
     */
    protected $username = '';

    /**
     * @var array
     */
    protected $structure = [];

    /**
     * @var boolean
     */
    protected $published = false;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setPublished(bool $published): void
    {
        $this->published = $published;
    }

    public function getPublished(): bool
    {
        return $this->published;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setStructure(array $structure): void
    {
        $this->structure = $structure;
    }

    public function &getStructure(): array
    {
        return $this->structure;
    }
}
