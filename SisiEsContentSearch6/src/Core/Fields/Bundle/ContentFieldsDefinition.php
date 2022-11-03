<?php

declare(strict_types=1);

namespace Sisi\SisiEsContentSearch6\Core\Fields\Bundle;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * Class ContentFieldsDefinition
 * @package Sisi\SisiEsContentSearch6\Core\Fields\Bundle
 * @SuppressWarnings(PHPMD)
 */

class ContentFieldsDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'sisi_escontent_fields';
    }

    public function getEntityClass(): string
    {
        return ContentFieldsEntity::class;
    }

    public function getCollectionClass(): string
    {
        return ContentFieldsCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection(
            [
                (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
                (new StringField('shop', 'shop')),
                (new StringField('label', 'label')),
                (new StringField('language', 'language')),
                (new StringField('display', 'display')),
                (new StringField('tokenizer', 'tokenizer')),
                (new IntField('minedge', 'minedge')),
                (new IntField('edge', 'edge')),
                (new StringField('filter1', 'filter1')),
                (new StringField('filter2', 'filter2')),
                (new StringField('filter3', 'filter3')),
                (new StringField('stemming', 'stemming')),
                (new StringField('stemmingstop', 'stemmingstop')),
                (new StringField('stop', 'stop')),
                (new StringField('maxhits', 'maxhits')),
                (new StringField('format', 'format')),
                (new StringField('pattern', 'pattern'))
            ]
        );
    }
}
