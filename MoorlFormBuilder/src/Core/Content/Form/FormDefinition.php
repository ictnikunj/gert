<?php declare(strict_types=1);

namespace MoorlFormBuilder\Core\Content\Form;

use MoorlFormBuilder\Core\Content\Aggregate\FormProduct\FormProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Media\Aggregate\MediaFolder\MediaFolderDefinition;

use Shopware\Core\Content\MailTemplate\MailTemplateDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;

class FormDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'moorl_form';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return FormCollection::class;
    }

    public function getEntityClass(): string
    {
        return FormEntity::class;
    }

    public function getDefaults(): array
    {
        return [
            'useSassCompiler' => false,
            'bootstrapGrid' => true,
            'bootstrapWideSpacing' => false,
            'sendCopyType' => 'disabled',
            'type' => 'cms',
        ];
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            new FkField('mail_template_id', 'mailTemplateId', MailTemplateDefinition::class),
            new FkField('customer_mail_template_id', 'customerMailTemplateId', MailTemplateDefinition::class),
            new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class),
            new FkField('media_folder_id', 'mediaFolderId', MediaFolderDefinition::class),
            new BoolField('active', 'active'),
            new BoolField('privacy', 'privacy'),
            new BoolField('use_captcha', 'useCaptcha'),
            new BoolField('use_trans', 'useTrans'),
            new BoolField('send_copy', 'sendCopy'),
            new BoolField('insert_database', 'insertDatabase'),
            new BoolField('locked', 'locked'),
            new BoolField('bootstrap_grid', 'bootstrapGrid'),
            new BoolField('bootstrap_wide_spacing', 'bootstrapWideSpacing'),
            new BoolField('use_sass_compiler', 'useSassCompiler'),
            new BoolField('send_mail', 'sendMail'),
            new BoolField('insert_newsletter', 'insertNewsletter'),
            new BoolField('insert_history', 'insertHistory'),
            new IntField('max_file_size', 'maxFileSize'),
            (new StringField('name', 'name'))->addFlags(new Required()),
            (new StringField('send_copy_type', 'sendCopyType'))->addFlags(new Required()),
            new StringField('media_folder', 'mediaFolder'),
            new StringField('action', 'action'),
            new StringField('file_upload_method', 'fileUploadMethod'),
            new JsonField('submit_text', 'submitText'),
            new JsonField('redirect_conditions', 'redirectConditions'),
            (new StringField('type', 'type'))->addFlags(new Required()),
            new StringField('email_receiver', 'emailReceiver'),
            new JsonField('success_message', 'successMessage'),
            new StringField('redirect_to', 'redirectTo'),
            new StringField('redirect_params', 'redirectParams'),
            new StringField('related_entity', 'relatedEntity'),
            new LongTextField('stylesheet', 'stylesheet'),
            new JsonField('label', 'label'),
            new JsonField('data', 'data'),
            new CustomFields(),
            new ManyToManyAssociationField(
                'products',
                ProductDefinition::class,
                FormProductDefinition::class,
                'moorl_form_id',
                'product_id'
            ),
        ]);
    }
}
