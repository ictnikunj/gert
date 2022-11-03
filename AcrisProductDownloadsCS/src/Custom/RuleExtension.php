<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Custom;

use Acris\ProductDownloads\Custom\Aggregate\DownloadTabRuleDefinition\DownloadTabRuleDefinition;
use Shopware\Core\Content\Rule\RuleDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class RuleExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new ManyToManyAssociationField(
                'acrisDownloadTabs',
                ProductDownloadTabDefinition::class,
                DownloadTabRuleDefinition::class,
                'rule_id',
                'tab_id'
            ))
        );
    }

    public function getDefinitionClass(): string
    {
        return RuleDefinition::class;
    }
}
