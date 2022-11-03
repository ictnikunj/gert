<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Custom\Aggregate\ProductDownloadLanguage;

use Acris\ProductDownloads\Custom\ProductDownloadDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;
use Shopware\Core\System\Language\LanguageDefinition;

class ProductDownloadLanguageDefinition extends MappingEntityDefinition
{
    public CONST ENTITY_NAME = 'acris_product_download_language';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new FkField('download_id', 'downloadId', ProductDownloadDefinition::class))->addFlags(new PrimaryKey(), new Required(), new ApiAware()),
            (new FkField('language_id', 'languageId', LanguageDefinition::class))->addFlags(new PrimaryKey(), new Required(), new ApiAware()),
            (new ManyToOneAssociationField('download', 'download_id', ProductDownloadDefinition::class))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('language', 'language_id', LanguageDefinition::class))->addFlags(new ApiAware()),
            (new CreatedAtField())->addFlags(new ApiAware())
        ]);
    }
}
