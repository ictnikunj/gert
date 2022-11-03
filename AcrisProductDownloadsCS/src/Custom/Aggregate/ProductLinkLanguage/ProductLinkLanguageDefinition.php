<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Custom\Aggregate\ProductLinkLanguage;

use Acris\ProductDownloads\Custom\ProductLinkDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;
use Shopware\Core\System\Language\LanguageDefinition;

class ProductLinkLanguageDefinition extends MappingEntityDefinition
{
    public CONST ENTITY_NAME = 'acris_product_link_language';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new FkField('link_id', 'linkId', ProductLinkDefinition::class))->addFlags(new PrimaryKey(), new Required(), new ApiAware()),
            (new FkField('language_id', 'languageId', LanguageDefinition::class))->addFlags(new PrimaryKey(), new Required(), new ApiAware()),
            (new ManyToOneAssociationField('link', 'link_id', ProductLinkDefinition::class))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('language', 'language_id', LanguageDefinition::class))->addFlags(new ApiAware()),
            (new CreatedAtField())->addFlags(new ApiAware())
        ]);
    }
}
