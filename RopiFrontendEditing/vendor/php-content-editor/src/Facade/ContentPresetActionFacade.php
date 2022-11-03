<?php
namespace Ropi\ContentEditor\Facade;

use Ropi\ContentEditor\ContentPreset\ContentPresetInterface;
use Ropi\ContentEditor\Environment\ContentEditorEnvironmentInterface;
use Ropi\ContentEditor\Environment\Exception\RequestBodyParseException;
use Ropi\ContentEditor\Storage\ContentPresetStorageInterface;

class ContentPresetActionFacade
{

    /**
     * @var ContentEditorEnvironmentInterface
     */
    private $contentEditorEnvironment;

    /**
     * @var ContentPresetStorageInterface
     */
    private $contentPresetStorage;

    public function __construct(
        ContentEditorEnvironmentInterface $contentEditorEnvironment,
        ContentPresetStorageInterface $contentPresetStorage
    ) {
        $this->contentEditorEnvironment = $contentEditorEnvironment;
        $this->contentPresetStorage = $contentPresetStorage;
    }

    public function getContentEditorEnvironment(): ContentEditorEnvironmentInterface
    {
        return $this->contentEditorEnvironment;
    }

    public function getContentPresetStorage(): ContentPresetStorageInterface
    {
        return $this->contentPresetStorage;
    }

    /**
     * @throws RequestBodyParseException
     */
    public function save(string $name): void
    {
        $payload = $this->getContentEditorEnvironment()->getParsedRequestBody();

        $this->getContentPresetStorage()->save(
            $name,
            $payload['data'] ?? []
        );
    }

    public function delete(string $name): void
    {
        $this->getContentPresetStorage()->delete($name);
    }

    /**
     * @return ContentPresetInterface[]
     */
    public function loadAll(): array
    {
        $this->getContentPresetStorage()->loadAll();
    }
}
