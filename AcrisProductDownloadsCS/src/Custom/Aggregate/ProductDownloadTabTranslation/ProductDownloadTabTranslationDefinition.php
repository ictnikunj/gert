<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Custom\Aggregate\ProductDownloadTabTranslation;

use Acris\ProductDownloads\Custom\ProductDownloadTabDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class ProductDownloadTabTranslationDefinition extends EntityTranslationDefinition
{
    public CONST ENTITY_NAME = 'acris_download_tab_translation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return ProductDownloadTabTranslationCollection::class;
    }

    public function getEntityClass(): string
    {
        return ProductDownloadTabTranslationEntity::class;
    }

    public function getParentDefinitionClass(): string
    {
        return ProductDownloadTabDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new StringField('display_name', 'displayName'))->addFlags(new Required(), new ApiAware()),
            (new CustomFields())->addFlags(new ApiAware())
        ]);
    }
}
