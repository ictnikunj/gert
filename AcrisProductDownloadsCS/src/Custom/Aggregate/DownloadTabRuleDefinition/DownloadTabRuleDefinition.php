<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Custom\Aggregate\DownloadTabRuleDefinition;

use Acris\ProductDownloads\Custom\ProductDownloadTabDefinition;
use Shopware\Core\Content\Rule\RuleDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;

class DownloadTabRuleDefinition extends MappingEntityDefinition
{

    public CONST ENTITY_NAME = 'acris_download_tab_rule';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new FkField('tab_id', 'tabId', ProductDownloadTabDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            (new ReferenceVersionField(ProductDownloadTabDefinition::class))->addFlags(new Required()),
            (new FkField('rule_id', 'ruleId', RuleDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            new ManyToOneAssociationField('tab', 'tab_id', ProductDownloadTabDefinition::class),
            new ManyToOneAssociationField('rule', 'rule_id', RuleDefinition::class),
            new CreatedAtField(),
        ]);
    }
}
