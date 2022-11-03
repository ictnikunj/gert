<?php declare(strict_types=1);

namespace MoorlFormBuilder\Core\Content\FormAppointment;

use MoorlFormBuilder\Core\Content\Form\FormDefinition;
use MoorlFoundation\Core\Framework\DataAbstractionLayer\Field\Flags\EditField;
use MoorlFoundation\Core\Framework\DataAbstractionLayer\Field\Flags\LabelProperty;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;

class FormAppointmentDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'moorl_form_appointment';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return FormAppointmentCollection::class;
    }

    public function getEntityClass(): string
    {
        return FormAppointmentEntity::class;
    }

    public function getDefaults(): array
    {
        return [];
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new FkField('moorl_form_id', 'formId', FormDefinition::class)),
            new FkField('product_id', 'productId', ProductDefinition::class),
            new FkField('order_id', 'orderId', OrderDefinition::class),
            new FkField('customer_id', 'customerId', CustomerDefinition::class),
            new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class),
            (new BoolField('active', 'active'))->addFlags(new EditField('switch')),
            (new StringField('form_element', 'formElement'))->addFlags(new Required(), new EditField('text')),
            (new DateTimeField('start', 'start'))->addFlags(new Required(), new EditField('text')),
            (new DateTimeField('end', 'end'))->addFlags(),

            (new ManyToOneAssociationField('form', 'moorl_form_id', FormDefinition::class))->addFlags(new ApiAware(), new EditField(), new LabelProperty('name')),
            (new ManyToOneAssociationField('product', 'product_id', ProductDefinition::class))->addFlags(new ApiAware(), new EditField(), new LabelProperty('orderNumber')),
            (new ManyToOneAssociationField('order', 'order_id', OrderDefinition::class))->addFlags(new ApiAware(), new EditField(), new LabelProperty('orderNumber')),
            (new ManyToOneAssociationField('customer', 'customer_id', CustomerDefinition::class))->addFlags(new ApiAware(), new EditField(), new LabelProperty('email')),
        ]);
    }
}
