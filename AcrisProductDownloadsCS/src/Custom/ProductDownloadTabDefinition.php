<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Custom;

use Acris\ProductDownloads\Custom\Aggregate\DownloadTabRuleDefinition\DownloadTabRuleDefinition;
use Acris\ProductDownloads\Custom\Aggregate\ProductDownloadTabTranslation\ProductDownloadTabTranslationDefinition;
use Shopware\Core\Content\Rule\RuleDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\SearchRanking;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\SetNullOnDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class ProductDownloadTabDefinition extends EntityDefinition
{
    public CONST ENTITY_NAME = 'acris_download_tab';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }
    public function getCollectionClass(): string
    {
        return ProductDownloadTabCollection::class;
    }
    public function getEntityClass(): string
    {
        return ProductDownloadTabEntity::class;
    }
    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required(), new ApiAware()),
            (new StringField('internal_id','internalId'))->addFlags(new Required(), new SearchRanking(SearchRanking::MIDDLE_SEARCH_RANKING), new ApiAware()),
            (new TranslatedField('displayName'))->addFlags(new ApiAware()),
            (new IntField('priority', 'priority'))->addFlags(new ApiAware()),

            (new OneToManyAssociationField('acrisDownloads', ProductDownloadDefinition::class, 'download_tab_id','id'))->addFlags(new SetNullOnDelete()),
            (new TranslationsAssociationField(ProductDownloadTabTranslationDefinition::class, 'acris_download_tab_id'))->addFlags(new ApiAware()),
            (new ManyToManyAssociationField('rules', RuleDefinition::class, DownloadTabRuleDefinition::class, 'tab_id', 'rule_id')),
        ]);
    }
}
