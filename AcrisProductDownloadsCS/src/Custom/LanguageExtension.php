<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Custom;

use Acris\ProductDownloads\Custom\Aggregate\ProductDownloadLanguage\ProductDownloadLanguageDefinition;
use Acris\ProductDownloads\Custom\Aggregate\ProductLinkLanguage\ProductLinkLanguageDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Inherited;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\Language\LanguageDefinition;

class LanguageExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new ManyToManyAssociationField(
                'acrisDownloads',
                ProductDownloadDefinition::class,
                ProductDownloadLanguageDefinition::class,
                'language_id',
                'download_id'
            ))->addFlags(new CascadeDelete(), new ApiAware())
        );

        $collection->add(
            (new ManyToManyAssociationField(
                'acrisLinks',
                ProductLinkDefinition::class,
                ProductLinkLanguageDefinition::class,
                'language_id',
                'link_id'
            ))->addFlags(new CascadeDelete(), new ApiAware())
        );
    }

    public function getDefinitionClass(): string
    {
        return LanguageDefinition::class;
    }
}
