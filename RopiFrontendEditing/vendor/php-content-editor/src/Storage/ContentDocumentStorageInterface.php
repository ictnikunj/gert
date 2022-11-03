<?php
namespace Ropi\ContentEditor\Storage;

use Ropi\ContentEditor\ContentDocument\ContentDocumentInterface;
use Ropi\ContentEditor\Storage\Exception\VersionNotFoundException;

interface ContentDocumentStorageInterface
{

    public function save(array $structure, bool $publish, string $username): void;

    public function unpublishForDocumentContext(array $documentContext): void;

    public function loadForDocumentContext(array $documentContext): ContentDocumentInterface;

    public function loadLatestVersionsForDocumentContext(array $documentContext): array;

    /**
     * @throws VersionNotFoundException
     */
    public function revert(string $versionId, string $username): void;
}
