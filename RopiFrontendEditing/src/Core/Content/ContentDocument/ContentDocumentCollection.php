<?php declare(strict_types=1);

namespace Ropi\FrontendEditing\Core\Content\ContentDocument;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class ContentDocumentCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ContentDocumentEntity::class;
    }
}