<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Custom;

use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Inherited;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class MediaExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new OneToManyAssociationField(
                'acrisDownloads',
                ProductDownloadDefinition::class,
                'media_id',
                'id')
            )->addFlags(new CascadeDelete(), new ApiAware())
        );

        $collection->add(
            (new OneToManyAssociationField(
                'acrisDownloads',
                ProductDownloadDefinition::class,
                'preview_media_id',
                'id')
            )->addFlags(new CascadeDelete(), new ApiAware())
        );
    }

    public function getDefinitionClass(): string
    {
        return MediaDefinition::class;
    }
}
