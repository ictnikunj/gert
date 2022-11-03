<?php
namespace Ropi\ContentEditor\ContentPreset;

trait ContentPresetTrait
{

    /**
     * @var string|null
     */
    protected $id;

    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var array
     */
    protected $structure = [];

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return (string) ($this->getStructure()['meta']['type'] ?? '');
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
