<?php declare(strict_types=1);

namespace MediaRedirect\Core\Content\MediaRedirect;

use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\AllowHtml;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class MediaRedirectDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'ict_media_redirect';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return MediaRedirectEntity::class;
    }
    public function getCollectionClass(): string
    {
        return MediaRedirectCollection::class;
    }
    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new LongTextField('url', 'url'))->addFlags(new AllowHtml()),
            (new FkField('media_id', 'mediaId', MediaDefinition::class)),
            new OneToOneAssociationField('media', 'media_id', 'id', MediaDefinition::class, false)
        ]);
    }
}
