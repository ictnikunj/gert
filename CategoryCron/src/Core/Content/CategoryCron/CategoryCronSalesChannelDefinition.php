<?php declare(strict_types=1);

namespace CategoryCron\Core\Content\CategoryCron;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;

class CategoryCronSalesChannelDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'category_cron_saleschannel';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            new IdField('sales_channel_id', 'salesChannelId'),
            new DateTimeField('last_usage_at', 'lastUsageAt'),
            new UpdatedAtField(),
            new CreatedAtField(),
        ]);
    }
}
