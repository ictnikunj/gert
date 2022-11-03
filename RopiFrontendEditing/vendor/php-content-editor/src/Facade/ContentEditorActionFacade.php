<?php
namespace Ropi\ContentEditor\Facade;

use Ropi\ContentEditor\Environment\ContentEditorEnvironmentInterface;
use Ropi\ContentEditor\Environment\Exception\RequestBodyParseException;
use Ropi\ContentEditor\Facade\Exception\ExportException;
use Ropi\ContentEditor\Facade\Exception\ImportException;
use Ropi\ContentEditor\Facade\Exception\RevertException;
use Ropi\ContentEditor\Service\ContentDocumentNodeServiceInterface;
use Ropi\ContentEditor\Service\DocumentContextServiceInterface;
use Ropi\ContentEditor\Service\Exception\InvalidDocumentContextInStructureException;
use Ropi\ContentEditor\Storage\ContentDocumentStorageInterface;
use Ropi\ContentEditor\Storage\Exception\VersionNotFoundException;

class ContentEditorActionFacade
{
    /**
     * @var DocumentContextServiceInterface
     */
    private $documentContextService;

    /**
     * @var ContentEditorEnvironmentInterface
     */
    private $contentEditorEnvironment;

    /**
     * @var ContentDocumentStorageInterface
     */
    private $contentDocumentStorage;

    /**
     * @var ContentDocumentNodeServiceInterface
     */
    private $contentDocumentNodeService;

    public function __construct(
        DocumentContextServiceInterface $documentContextService,
        ContentEditorEnvironmentInterface $contentEditorEnvironment,
        ContentDocumentStorageInterface $contentDocumentStorage,
        ContentDocumentNodeServiceInterface $contentDocumentNodeService
    ) {
        $this->documentContextService = $documentContextService;
        $this->contentEditorEnvironment = $contentEditorEnvironment;
        $this->contentDocumentStorage = $contentDocumentStorage;
        $this->contentDocumentNodeService = $contentDocumentNodeService;
    }

    public function getDocumentContextService(): DocumentContextServiceInterface
    {
        return $this->documentContextService;
    }

    public function getContentEditorEnvironment(): ContentEditorEnvironmentInterface
    {
        return $this->contentEditorEnvironment;
    }

    public function getContentDocumentStorage(): ContentDocumentStorageInterface
    {
        return $this->contentDocumentStorage;
    }

    public function getContentDocumentNodeService(): ContentDocumentNodeServiceInterface
    {
        return $this->contentDocumentNodeService;
    }

    /**
     * @throws RequestBodyParseException
     */
    public function save(string $username): void
    {
        $payload = $this->getContentEditorEnvironment()->getParsedRequestBody();

        $this->getContentDocumentStorage()->save(
            $payload['data'] ?? [],
            $payload['publish'] ?? false,
            $username
        );
    }

    /**
     * @throws RequestBodyParseException
     * @throws InvalidDocumentContextInStructureException
     */
    public function unpublish(): void
    {
        $payload = $this->getContentEditorEnvironment()->getParsedRequestBody();
        $structure = $payload['data'] ?? [];

        $documentContext = $this->getDocumentContextService()->getDocumentContextFromStructure($structure);

        $this->getContentDocumentStorage()->unpublishForDocumentContext($documentContext);
    }

    /**
     * @throws InvalidDocumentContextInStructureException
     * @throws RequestBodyParseException
     */
    public function importFromDocumentContext(string $username): void
    {
        $payload = $this->getContentEditorEnvironment()->getParsedRequestBody();
        $structure = $payload['data'] ?? [];
        $sourceDocumentContext = $payload['sourceDocumentContext'] ?? [];
        $keepLanguageSpecificData = $payload['keepLanguageSpecificData'] ?? false;

        $sourceContentDocument = $this->getContentDocumentStorage()->loadForDocumentContext($sourceDocumentContext);

        $this->getContentDocumentStorage()->save(
            $this->mergeDocumentStructure($structure, $sourceContentDocument->getStructure(), $keepLanguageSpecificData),
            false,
            $username
        );
    }

    /**
     * @throws ExportException
     * @throws InvalidDocumentContextInStructureException
     * @throws RequestBodyParseException
     */
    public function exportToSalesChannels(string $username): void
    {
        $payload = $this->getContentEditorEnvironment()->getParsedRequestBody();
        $structure = $payload['data'] ?? [];
        $documentContextDeltas = $payload['documentContextDeltas'] ?? [];
        $keepLanguageSpecificData = $payload['keepLanguageSpecificData'] ?? false;
        $publish = $payload['publish'] ?? false;

        if (!is_array($documentContextDeltas) || empty($documentContextDeltas)) {
            throw new ExportException('Document context deltas are invalid or empty', 1617644076);
        }

        if (!is_array($structure)) {
            throw new ExportException('Export data is invalid', 1617644115);
        }

        if (!isset($structure['meta']['context'])
            || !is_array($structure['meta']['context'])
            || empty($structure['meta']['context'])
        ) {
            throw new ExportException(
                'Export data has no document context or document context is invalid',
                1617644120
            );
        }

        foreach ($documentContextDeltas as $documentContextDelta) {
            if (empty($documentContextDelta)) {
                continue;
            }

            $targetDocumentContext = array_merge($structure['meta']['context'], $documentContextDelta);
            $targetContentDocument = $this->getContentDocumentStorage()->loadForDocumentContext($targetDocumentContext);

            $this->getContentDocumentStorage()->save(
                $this->mergeDocumentStructure($targetContentDocument->getStructure(), $structure, $keepLanguageSpecificData),
                $publish,
                $username
            );
        }
    }

    /**
     * @throws ImportException
     * @throws InvalidDocumentContextInStructureException
     * @throws RequestBodyParseException
     */
    public function import(string $username): void
    {
        $payload = $this->getContentEditorEnvironment()->getParsedRequestBody();
        $structure = $payload['data'] ?? [];
        $keepLanguageSpecificData = $payload['keepLanguageSpecificData'] ?? false;

        $importData = json_decode($payload['importData'] ?? '', true);
        if (!is_array($importData)) {
            throw new ImportException('Failed to parse import data', 1569165261);
        }

        if (empty($importData)) {
            throw new ImportException('Import data is empty', 1592751416);
        }

        $this->getContentDocumentStorage()->save(
            $this->mergeDocumentStructure($structure, $importData, $keepLanguageSpecificData),
            false,
            $username
        );
    }

    /**
     * @throws RevertException
     * @throws InvalidDocumentContextInStructureException
     * @throws RequestBodyParseException
     * @throws VersionNotFoundException
     */
    public function revert(string $username): void
    {
        $payload = $this->getContentEditorEnvironment()->getParsedRequestBody();

        if (!isset($payload['versionId']) || !is_string($payload['versionId']) || !trim($payload['versionId'])) {
            throw new RevertException('Property "versionId" is missing or invalid in payload', 1572017037);
        }

        $this->getContentDocumentStorage()->revert($payload['versionId'], $username);
    }

    /**
     * @throws InvalidDocumentContextInStructureException
     */
    protected function mergeDocumentStructure(array $targetStructure, array $sourceStructure, bool $keepLanguageSpecificData): array
    {
        $targetDocumentContext = $this->getDocumentContextService()->getDocumentContextFromStructure($targetStructure);

        if ($keepLanguageSpecificData) {
            $structure = $this->getContentDocumentNodeService()->mergeConfiguration(
                $targetStructure,
                $sourceStructure,
                true
            );

            $structure = $this->getContentDocumentNodeService()->mergeConfiguration(
                $sourceStructure,
                $structure,
                false
            );

            $structure = $this->getContentDocumentNodeService()->mergeContents(
                $structure,
                $targetStructure
            );

            return $this->getDocumentContextService()->setDocumentContextForStructure(
                $structure,
                $targetDocumentContext
            );
        }

        return $this->getDocumentContextService()->setDocumentContextForStructure(
            $sourceStructure,
            $targetDocumentContext
        );
    }
}
