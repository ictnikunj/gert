<?php declare(strict_types=1);

namespace MoorlFormBuilder\Core\Content\Product;

use MoorlFormBuilder\Core\Content\Aggregate\FormProduct\FormProductDefinition;
use MoorlFormBuilder\Core\Content\Form\FormDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Inherited;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class ProductExtension extends EntityExtension
{
    public function getDefinitionClass(): string
    {
        return ProductDefinition::class;
    }

    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new ManyToManyAssociationField(
                'forms',
                FormDefinition::class,
                FormProductDefinition::class,
                'product_id',
                'moorl_form_id'
            ))->addFlags(new Inherited())
        );
    }
}
