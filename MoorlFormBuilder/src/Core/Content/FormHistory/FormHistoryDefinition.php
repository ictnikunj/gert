<?php declare(strict_types=1);

namespace MoorlFormBuilder\Core\Content\FormHistory;


use MoorlFormBuilder\Core\Content\Form\FormDefinition;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\SearchRanking;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Inherited;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\Country\CountryDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Shopware\Core\System\Salutation\SalutationDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\System\Tag\TagDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Media\Aggregate\MediaFolder\MediaFolderDefinition;


use Shopware\Core\Content\MailTemplate\MailTemplateDefinition;

class FormHistoryDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'moorl_form_history';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return FormHistoryCollection::class;
    }

    public function getEntityClass(): string
    {
        return FormHistoryEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            new FkField('moorl_form_id', 'formId', FormHistoryDefinition::class),
            new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class),
            new StringField('email', 'email'),
            new StringField('name', 'name'),
            new JsonField('media', 'media'),
            new JsonField('data', 'data'),
            new JsonField('request_data', 'requestData'),
            new BoolField('seen', 'seen'),
            new ManyToOneAssociationField('form', 'moorl_form_id', FormDefinition::class, 'id', true),
            new ManyToOneAssociationField('salesChannel', 'sales_channel_id', SalesChannelDefinition::class, 'id', true),
        ]);
    }
}
