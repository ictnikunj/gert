<?php declare(strict_types=1);

namespace Ropi\FrontendEditing\ContentEditor\Storage;

use Ropi\ContentEditor\ContentPreset\ContentPresetInterface;
use Ropi\ContentEditor\Environment\ContentEditorEnvironmentInterface;
use Ropi\ContentEditor\Storage\AbstractContentPresetStorage;
use Ropi\FrontendEditing\Core\Content\ContentPreset\ContentPresetEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;

class ContentPresetStorage extends AbstractContentPresetStorage
{
    /**
     * @var EntityRepositoryInterface
     */
    protected $contentPresetRepository;

    public function __construct(
        ContentEditorEnvironmentInterface $contentEditorEnvironment,
        EntityRepositoryInterface $contentPresetRepository
    ) {
        parent::__construct($contentEditorEnvironment);

        $this->contentPresetRepository = $contentPresetRepository;
    }

    public function load(string $name): ?ContentPresetInterface
    {
        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('name', $name));

        return $this->contentPresetRepository->search($criteria, $this->getContext())->first();
    }

    public function loadAll(): array
    {
        $criteria = (new Criteria())
            ->addSorting(new FieldSorting('name', FieldSorting::ASCENDING));

        return $this->contentPresetRepository->search($criteria, $this->getContext())->getEntities()->getElements();
    }

    public function delete(string $name): void
    {
        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('name', $name));

        $existingPresets = $this->contentPresetRepository->search($criteria, $this->getContext())->getElements();

        /** @var ContentPresetEntity $existingPreset */
        foreach ($existingPresets as $existingPreset) {
            $this->contentPresetRepository->delete(
                [
                    [
                        'id' => $existingPreset->getId()
                    ]
                ],
                $this->getContext()
            );
        }
    }

    protected function create(): ContentPresetInterface
    {
        return new ContentPresetEntity();
    }

    protected function persist(ContentPresetInterface $contentPreset): void
    {
        $this->contentPresetRepository->upsert(
            [
                [
                    'id' => $contentPreset->getId() ? $contentPreset->getId() : Uuid::randomHex(),
                    'name' => $contentPreset->getName(),
                    'structure' => $contentPreset->getStructure()
                ]
            ],
            $this->getContext()
        );
    }

    protected function getContext(): Context
    {
        return Context::createDefaultContext();
    }
}
