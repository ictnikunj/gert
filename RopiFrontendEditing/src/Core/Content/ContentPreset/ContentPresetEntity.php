<?php declare(strict_types=1);

namespace Ropi\FrontendEditing\Core\Content\ContentPreset;

use Ropi\ContentEditor\ContentPreset\ContentPresetInterface;
use Ropi\ContentEditor\ContentPreset\ContentPresetTrait;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;

class ContentPresetEntity extends Entity implements ContentPresetInterface
{
    use ContentPresetTrait;

    public function getCreationTime(): ?\DateTimeInterface
    {
        return $this->getCreatedAt();
    }

    public function setId(string $id): void
    {
        $this->id = $id;
        $this->_uniqueIdentifier = $id;
    }
}