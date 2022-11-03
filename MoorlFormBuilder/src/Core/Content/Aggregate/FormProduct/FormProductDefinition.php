<?php declare(strict_types=1);

namespace MoorlFormBuilder\Core\Content\Aggregate\FormProduct;

use MoorlFormBuilder\Core\Content\Form\FormDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;

class FormProductDefinition extends MappingEntityDefinition
{
    public const ENTITY_NAME = 'moorl_form_product';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new FkField('moorl_form_id', 'moorlFormId', FormDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            (new FkField('product_id', 'productId', ProductDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            (new ReferenceVersionField(ProductDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            new ManyToOneAssociationField(
                'form',
                'moorl_form_id',
                FormDefinition::class
            ),
            new ManyToOneAssociationField(
                'product',
                'product_id',
                ProductDefinition::class
            ),
            new CreatedAtField(),
        ]);
    }
}
