<?php
namespace Ropi\ContentEditor\Storage;

use Ropi\ContentEditor\ContentDocument\ContentDocumentInterface;
use Ropi\ContentEditor\Environment\ContentEditorEnvironmentInterface;
use Ropi\ContentEditor\Service\DocumentContextServiceInterface;
use Ropi\ContentEditor\Service\Exception\InvalidDocumentContextInStructureException;
use Ropi\ContentEditor\Storage\Exception\VersionNotFoundException;

abstract class AbstractContentDocumentStorage implements ContentDocumentStorageInterface
{

    /**
     * @var ContentEditorEnvironmentInterface
     */
    private $contentEditorEnvironment;

    /**
     * @var DocumentContextServiceInterface
     */
    private $documentContextService;

    public function __construct(
        ContentEditorEnvironmentInterface $contentEditorEnvironment,
        DocumentContextServiceInterface $documentContextService
    ) {
        $this->contentEditorEnvironment = $contentEditorEnvironment;
        $this->documentContextService = $documentContextService;
    }

    public function getContentEditorEnvironment(): ContentEditorEnvironmentInterface
    {
        return $this->contentEditorEnvironment;
    }

    public function getDocumentContextService(): DocumentContextServiceInterface
    {
        return $this->documentContextService;
    }

    /**
     * @throws InvalidDocumentContextInStructureException
     */
    public function save(array $structure, bool $publish, string $username): void
    {
        $documentContext = $this->getDocumentContextService()->getDocumentContextFromStructure($structure);

        if ($publish) {
            $this->unpublishForDocumentContext($documentContext);
        }

        $contentDocument = $this->create();
        $contentDocument->setStructure($structure);
        $contentDocument->setPublished($publish);
        $contentDocument->setUsername($username);

        $this->persist($contentDocument);

        if ($publish) {
            $this->clearCacheForDocumentContext($documentContext);
        }
    }

    public function unpublishForDocumentContext(array $documentContext): void
    {
        $contentDocument = $this->fetchPublished($documentContext);

        if (!$contentDocument) {
            return;
        }

        $contentDocument->setPublished(false);

        $this->persist($contentDocument);

        $this->clearCacheForDocumentContext($documentContext);
    }

    public function loadForDocumentContext(array $documentContext): ContentDocumentInterface
    {
        $contentDocument = null;

        if ($this->getContentEditorEnvironment()->editorOpened()) {
            $versionId = $this->contentEditorEnvironment->getRequestedVersionId();
            if ($versionId) {
                $contentDocument = $this->fetchVersion($versionId);
            } else {
                $contentDocument = $this->fetchCurrent($documentContext);
            }
        } else {
            $contentDocument = $this->fetchPublished($documentContext);
        }

        if (!$contentDocument) {
            $contentDocument = $this->create();
        }

        return $contentDocument;
    }

    public function loadLatestVersionsForDocumentContext(array $documentContext): array
    {
        $versions = [];
        $publishedTraversed = false;

        $contentDocuments = $this->fetchLatestVersions($documentContext);
        foreach ($contentDocuments as $contentDocument) {
            /* @noinspection PhpUnusedLocalVariableInspection */
            $time = '';

            /* @noinspection PhpUnusedLocalVariableInspection */
            $user = '';

            $published = false;

            if ($contentDocument->getCreationTime() instanceof \DateTimeInterface) {
                $time = $contentDocument->getCreationTime()->getTimestamp();
            } else {
                $time = 0;
            }

            if (trim($contentDocument->getUsername())) {
                $user = $contentDocument->getUsername();
            } else {
                $user = 'n/a';
            }

            if (!$publishedTraversed && $contentDocument->getPublished()) {
                $published = true;
                $publishedTraversed = true;
            }

            $versions[] = [
                'id' => $contentDocument->getId(),
                'published' => $published,
                'time' => $time,
                'user' => $user
            ];
        }

        return $versions;
    }

    /**
     * @throws VersionNotFoundException
     * @throws InvalidDocumentContextInStructureException
     */
    public function revert(string $versionId, string $username): void
    {
        $contentDocument = $this->fetchVersion($versionId);
        if (!$contentDocument) {
            throw new VersionNotFoundException('Version #' . $versionId . ' not found', 1559223266);
        }

        $this->save($contentDocument->getStructure(), false, $username);
    }

    abstract protected function create(): ContentDocumentInterface;

    abstract protected function fetchVersion(string $versionId): ?ContentDocumentInterface;

    /**
     * @return ContentDocumentInterface[]
     */
    abstract protected function fetchLatestVersions(array $documentContext): array;

    abstract protected function fetchCurrent(array $documentContext): ?ContentDocumentInterface;

    abstract protected function fetchPublished(array $documentContext): ?ContentDocumentInterface;

    abstract protected function persist(ContentDocumentInterface $contentDocument): void;

    abstract protected function clearCacheForDocumentContext(array $documentContext): void;
}
