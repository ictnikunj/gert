<?php declare(strict_types=1);

namespace Ropi\FrontendEditing\Core\Content\ContentPreset;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class ContentPresetCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ContentPresetEntity::class;
    }
}