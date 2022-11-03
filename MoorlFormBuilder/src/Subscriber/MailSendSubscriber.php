<?php declare(strict_types=1);

namespace MoorlFormBuilder\Subscriber;

use MoorlFormBuilder\MoorlFormBuilder;
use Shopware\Core\Content\Mail\Service\AbstractMailService;
use Shopware\Core\Content\MailTemplate\Exception\MailEventConfigurationException;
use Shopware\Core\Content\MailTemplate\MailTemplateEntity;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Contracts\EventDispatcher\Event;
use Shopware\Core\Framework\Event\EventData\EventDataType;
use Shopware\Core\Framework\Event\MailAware;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MailSendSubscriber implements EventSubscriberInterface
{
    public const ACTION_NAME = MoorlFormBuilder::MAIL_TEMPLATE_MAIL_SEND_ACTION;
    public const MAIL_TEMPLATE_TYPE = 'moorl_form_builder_cms';

    private AbstractMailService $mailService;
    private EntityRepositoryInterface $mailTemplateRepository;
    private MediaService $mediaService;

    public function __construct(
        AbstractMailService $mailService,
        EntityRepositoryInterface $mailTemplateRepository,
        MediaService $mediaService
    )
    {
        $this->mailService = $mailService;
        $this->mailTemplateRepository = $mailTemplateRepository;
        $this->mediaService = $mediaService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            self::ACTION_NAME => 'sendMail',
        ];
    }

    /**
     * @throws MailEventConfigurationException
     */
    public function sendMail(/*MailAware*/ $event): void
    {
        $form = $event->getForm();
        if (!$form) {
            return;
        }

        $mailTemplate = $this->getMailTemplate($form->getMailTemplateId(), $event->getContext());
        if (!$mailTemplate) {
            return;
        }

        $data = new DataBag();
        $data->set('recipients', $event->getMailStruct()->getRecipients());

        if ($form->getReplyTo()) {
            $data->set('replyTo', $form->getReplyTo());
        }

        $data->set('senderName', $mailTemplate->getTranslation('senderName'));
        $data->set('salesChannelId', $event->getSalesChannelId());

        $data->set('templateId', $mailTemplate->getId());
        $data->set('customFields', $mailTemplate->getCustomFields());
        $data->set('contentHtml', $mailTemplate->getTranslation('contentHtml'));
        $data->set('contentPlain', $mailTemplate->getTranslation('contentPlain'));
        $data->set('subject', $mailTemplate->getTranslation('subject'));
        $data->set('mediaIds', []);

        $attachments = [];

        if ($event->getMedias()) {
            foreach ($event->getMedias() as $mailEventMedia) {
                if (!$mailEventMedia) {
                    continue;
                }

                $fileName = mb_substr($mailEventMedia->getFileName(), 33);
                $updatedMedia = clone $mailEventMedia;
                $updatedMedia->setFileName($fileName);

                $attachments[] = $this->mediaService->getAttachment(
                    $updatedMedia,
                    $event->getContext()
                );
            }
        } else {
            $attachments = array_merge($attachments, $form->getBinAttachments());
        }

        if ($mailTemplate->getMedia()) {
            foreach ($mailTemplate->getMedia() as $mailTemplateMedia) {
                if (!$mailTemplateMedia->getMedia()) {
                    continue;
                }
                if ($mailTemplateMedia->getLanguageId()  && $mailTemplateMedia->getLanguageId() !== $event->getContext()->getLanguageId()) {
                    continue;
                }

                $attachments[] = $this->mediaService->getAttachment(
                    $mailTemplateMedia->getMedia(),
                    $event->getContext()
                );
            }
        }
        if (!empty($attachments)) {
            $data->set('binAttachments', $attachments);
        }

        $this->mailService->send(
            $data->all(),
            $event->getContext(),
            $this->getTemplateData($event)
        );
    }

    private function getMailTemplate(?string $mailTemplateId, Context $context): MailTemplateEntity
    {
        $mailTemplate = null;

        if ($mailTemplateId) {
            $criteria = new Criteria([$mailTemplateId]);
            $criteria->addAssociation('media.media');
            $criteria->setLimit(1);

            $mailTemplate = $this->mailTemplateRepository->search($criteria, $context)->first();
        }

        if (!$mailTemplate) {
            $criteria = new Criteria();
            $criteria->addAssociation('media.media');
            $criteria->setLimit(1);
            $criteria->addFilter(new EqualsFilter('mailTemplateType.technicalName', self::MAIL_TEMPLATE_TYPE));

            $mailTemplate = $this->mailTemplateRepository->search($criteria, $context)->first();
        }

        return $mailTemplate;
    }

    /**
     * @throws MailEventConfigurationException
     */
    private function getTemplateData(/*MailAware*/ $event): array
    {
        $data = [];
        /* @var EventDataType $item */
        foreach (array_keys($event::getAvailableData()->toArray()) as $key) {
            $getter = 'get' . ucfirst($key);
            if (method_exists($event, $getter)) {
                $data[$key] = $event->$getter();
            } else {
                throw new MailEventConfigurationException('Data for ' . $key . ' not available.', get_class($event));
            }
        }

        return $data;
    }
}
