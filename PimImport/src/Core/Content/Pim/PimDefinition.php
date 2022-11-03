<?php declare(strict_types=1);

namespace PimImport\Core\Content\Pim;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;

class PimDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'pim_product';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }
    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            new StringField('product_number','productNumber'),
            new DateTimeField('last_usage_at', 'lastUsageAt'),
            new DateTimeField('last_related_cross_usage_at', 'lastRelatedCrossSellUsage'),
            new DateTimeField('last_productpart_cross_usage_at', 'lastProductPartCrossSellUsage'),
            new DateTimeField('last_addon_cross_usage_at', 'lastAddonCrossSellUsage'),
            new UpdatedAtField(),
            new CreatedAtField(),
        ]);
    }
}
