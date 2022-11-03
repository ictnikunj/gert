<?php declare(strict_types=1);

namespace MoorlFormBuilder\Core\Event;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\EventData\EventDataCollection;
use Shopware\Core\Framework\Event\EventData\MailRecipientStruct;
use Shopware\Core\Framework\Event\EventData\ObjectType;
use Shopware\Core\Framework\Event\MailAware;
use Shopware\Core\Framework\Event\SalesChannelAware;
use Symfony\Contracts\EventDispatcher\Event;
use MoorlFormBuilder\Core\Content\Form\FormEntity;
use Shopware\Core\Content\Media\MediaCollection;

final class CmsFormEvent extends Event /*implements SalesChannelAware, MailAware*/
{
    public const EVENT_NAME = 'moorl_form_builder.cms.form.send';

    private Context $context;
    private string $salesChannelId;
    private MailRecipientStruct $recipients;
    private FormEntity $form;
    private ?MediaCollection $medias;

    public function __construct(
        Context $context,
        string $salesChannelId,
        MailRecipientStruct $recipients,
        FormEntity $form,
        ?MediaCollection $medias
    )
    {
        $this->context = $context;
        $this->salesChannelId = $salesChannelId;
        $this->recipients = $recipients;
        $this->form = $form;
        $this->medias = $medias;
    }

    /**
     * @return MediaCollection|null
     */
    public function getMedias(): ?MediaCollection
    {
        return $this->medias;
    }

    public static function getAvailableData(): EventDataCollection
    {
        return (new EventDataCollection())
            ->add('form', new ObjectType());
    }

    public function getName(): string
    {
        return self::EVENT_NAME;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getMailStruct(): MailRecipientStruct
    {
        return $this->recipients;
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    public function getForm(): ?FormEntity
    {
        return $this->form;
    }
}
