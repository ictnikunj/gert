<?php
namespace Ropi\ContentEditor\Storage;

use Ropi\ContentEditor\ContentPreset\ContentPresetInterface;

interface ContentPresetStorageInterface
{

    public function save(string $name, array $structure): void;

    public function delete(string $name): void;

    public function load(string $name): ?ContentPresetInterface;

    /**
     * @return ContentPresetInterface[]
     */
    public function loadAll(): array;
}
