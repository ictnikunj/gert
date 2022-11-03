<?php declare(strict_types=1);

namespace MediaRedirect\Core\Content\Extension;

use MediaRedirect\Core\Content\MediaRedirect\MediaRedirectDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;

class MediaExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new OneToOneAssociationField(
                'media',
                'id',
                'media_id',
                MediaRedirectDefinition::class
            ))->addFlags(new CascadeDelete())
        );
    }

    public function getDefinitionClass(): string
    {
        return MediaDefinition::class;
    }
}
