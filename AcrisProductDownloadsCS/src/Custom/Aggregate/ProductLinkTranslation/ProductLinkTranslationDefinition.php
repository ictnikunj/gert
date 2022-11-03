<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Custom\Aggregate\ProductLinkTranslation;

use Acris\ProductDownloads\Custom\ProductLinkDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\AllowHtml;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class ProductLinkTranslationDefinition extends EntityTranslationDefinition
{
    public CONST ENTITY_NAME = 'acris_product_link_translation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return ProductLinkTranslationCollection::class;
    }

    public function getEntityClass(): string
    {
        return ProductLinkTranslationEntity::class;
    }

    public function getParentDefinitionClass(): string
    {
        return ProductLinkDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            new StringField('title', 'title'),
            (new LongTextField('description', 'description'))->addFlags(new AllowHtml(), new ApiAware()),
            (new CustomFields())->addFlags(new ApiAware())
        ]);
    }
}
