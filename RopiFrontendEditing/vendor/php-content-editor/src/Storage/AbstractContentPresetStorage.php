<?php
namespace Ropi\ContentEditor\Storage;

use Ropi\ContentEditor\ContentPreset\ContentPresetInterface;
use Ropi\ContentEditor\Environment\ContentEditorEnvironmentInterface;
use Ropi\ContentEditor\Storage\Exception\MetaTypeMissingException;

abstract class AbstractContentPresetStorage implements ContentPresetStorageInterface
{

    /**
     * @var ContentEditorEnvironmentInterface
     */
    private $contentEditorEnvironment;

    public function __construct(ContentEditorEnvironmentInterface $contentEditorEnvironment)
    {
        $this->contentEditorEnvironment = $contentEditorEnvironment;
    }

    public function getContentEditorEnvironment(): ContentEditorEnvironmentInterface
    {
        return $this->contentEditorEnvironment;
    }

    /**
     * @param string $name
     * @param array $structure
     * @throws MetaTypeMissingException
     */
    public function save(string $name, array $structure): void
    {
        if (!isset($structure['meta']['type']) || !is_string($structure['meta']['type'])) {
            throw new MetaTypeMissingException(
                'Can not save content preset, because meta type is missing or invalid in given structure',
                1612273404
            );
        }

        $contentPreset = $this->load($name);
        if (!$contentPreset) {
            $contentPreset = $this->create();
        }

        $contentPreset->setStructure($structure);
        $contentPreset->setName($name);

        $this->persist($contentPreset);
    }

    abstract protected function create(): ContentPresetInterface;

    abstract protected function persist(ContentPresetInterface $contentPreset): void;
}
