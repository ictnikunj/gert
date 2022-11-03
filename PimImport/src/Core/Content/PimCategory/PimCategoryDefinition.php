<?php declare(strict_types=1);

namespace PimImport\Core\Content\PimCategory;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;

class PimCategoryDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'pim_category';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return PimCategoryEntity::class;
    }

    public function getCollectionClass(): string
    {
        return PimCategoryCollection::class;
    }
    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            new IdField('category_id', 'categoryId'),
            new StringField('category_code', 'categoryCode'),
            new IdField('sales_channel_id', 'salesChannelId'),
            new DateTimeField('last_usage_at', 'lastUsageAt'),
            new UpdatedAtField(),
            new CreatedAtField(),
        ]);
    }
}
