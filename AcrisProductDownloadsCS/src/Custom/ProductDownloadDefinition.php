<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Custom;

use Acris\ProductDownloads\Custom\Aggregate\ProductDownloadLanguage\ProductDownloadLanguageDefinition;
use Acris\ProductDownloads\Custom\Aggregate\ProductDownloadTranslation\ProductDownloadTranslationDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyIdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\Language\LanguageDefinition;

class ProductDownloadDefinition extends EntityDefinition
{
    public CONST ENTITY_NAME = 'acris_product_download';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }
    public function getCollectionClass(): string
    {
        return ProductDownloadCollection::class;
    }
    public function getEntityClass(): string
    {
        return ProductDownloadEntity::class;
    }
    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required(), new ApiAware()),
            (new FkField('media_id', 'mediaId', MediaDefinition::class))->addFlags(new Required(), new ApiAware()),
            (new FKField('preview_media_id', 'previewMediaId', MediaDefinition::class))->addFlags(new ApiAware()),
            (new FkField('product_id', 'productId', ProductDefinition::class))->addFlags(new Required(), new ApiAware()),
            (new FkField('download_tab_id', 'downloadTabId', ProductDownloadTabDefinition::class))->addFlags(new ApiAware()),
            (new ReferenceVersionField(ProductDefinition::class))->addFlags(new Required(), new ApiAware()),
            (new ManyToManyIdField('language_ids', 'languageIds', 'languages'))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('media', 'media_id', MediaDefinition::class, 'id'))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('previewMedia', 'preview_media_id', MediaDefinition::class, 'id'))->addFlags(new ApiAware()),
            (new ManyToManyAssociationField('languages', LanguageDefinition::class, ProductDownloadLanguageDefinition::class, "download_id", "language_id"))->addFlags(new CascadeDelete(), new ApiAware()),
            (new ManyToOneAssociationField('products', 'product_id', ProductDefinition::class, 'id'))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('downloadTab', 'download_tab_id', ProductDownloadTabDefinition::class, 'id'))->addFlags(new ApiAware()),
            (new IntField('position', 'position'))->addFlags(new ApiAware()),
            (new TranslatedField('title'))->addFlags(new ApiAware()),
            (new TranslatedField('description'))->addFlags(new ApiAware()),
            (new TranslationsAssociationField(ProductDownloadTranslationDefinition::class, 'acris_product_download_id'))->addFlags(new ApiAware()),
            (new BoolField('preview_image_enabled', 'previewImageEnabled'))->addFlags(new ApiAware())
        ]);
    }
}
