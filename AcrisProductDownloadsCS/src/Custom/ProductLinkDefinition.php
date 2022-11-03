<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Custom;

use Acris\ProductDownloads\Custom\Aggregate\ProductLinkLanguage\ProductLinkLanguageDefinition;
use Acris\ProductDownloads\Custom\Aggregate\ProductLinkTranslation\ProductLinkTranslationDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyIdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\Language\LanguageDefinition;

class ProductLinkDefinition extends EntityDefinition
{
    public CONST ENTITY_NAME = 'acris_product_link';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }
    public function getCollectionClass(): string
    {
        return ProductLinkCollection::class;
    }
    public function getEntityClass(): string
    {
        return ProductLinkEntity::class;
    }
    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required(), new ApiAware()),
            (new FkField('product_id', 'productId', ProductDefinition::class))->addFlags(new Required(), new ApiAware()),
            (new ReferenceVersionField(ProductDefinition::class))->addFlags(new Required(), new ApiAware()),
            (new ManyToManyIdField('language_ids', 'languageIds', 'languages'))->addFlags(new ApiAware()),
            (new ManyToManyAssociationField('languages', LanguageDefinition::class, ProductLinkLanguageDefinition::class, "link_id", "language_id"))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('product', 'product_id', ProductDefinition::class, 'id'))->addFlags(new ApiAware()),
            (new IntField('position', 'position'))->addFlags(new ApiAware()),
            (new TranslatedField('title'))->addFlags(new ApiAware()),
            (new TranslatedField('description'))->addFlags(new ApiAware()),
            (new StringField('url', 'url'))->addFlags(new Required(), new ApiAware()),
            (new BoolField('link_target', 'linkTarget'))->addFlags(new ApiAware()),
            (new TranslationsAssociationField(ProductLinkTranslationDefinition::class, 'acris_product_link_id'))->addFlags(new ApiAware())
        ]);
    }
}
