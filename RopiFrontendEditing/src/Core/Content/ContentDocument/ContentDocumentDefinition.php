<?php declare(strict_types=1);

namespace Ropi\FrontendEditing\Core\Content\ContentDocument;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Computed;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class ContentDocumentDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'ropi_frontend_editing_content_document';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return ContentDocumentEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new StringField('username', 'username'))->addFlags(new Required()),
            (new BoolField('published', 'published'))->addFlags(new Required()),
            (new StringField('sales_channel_id', 'salesChannelId'))->addFlags(new Computed()),
            (new StringField('bundle', 'bundle'))->addFlags(new Computed()),
            (new StringField('controller', 'controller'))->addFlags(new Computed()),
            (new StringField('action', 'action'))->addFlags(new Computed()),
            (new StringField('language_id', 'languageId'))->addFlags(new Computed()),
            (new StringField('subcontext', 'subcontext'))->addFlags(new Computed()),
            (new JsonField('structure', 'structure')),
            new CreatedAtField(),
            new UpdatedAtField(),
        ]);
    }
}