<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Custom\Aggregate\ProductDownloadTranslation;

use Acris\ProductDownloads\Custom\ProductDownloadDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\AllowHtml;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class ProductDownloadTranslationDefinition extends EntityTranslationDefinition
{
    public CONST ENTITY_NAME = 'acris_product_download_translation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return ProductDownloadTranslationCollection::class;
    }

    public function getEntityClass(): string
    {
        return ProductDownloadTranslationEntity::class;
    }

    public function getParentDefinitionClass(): string
    {
        return ProductDownloadDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new StringField('title', 'title'))->addFlags(new ApiAware()),
            (new LongTextField('description', 'description'))->addFlags(new AllowHtml(), new ApiAware())
        ]);
    }
}
