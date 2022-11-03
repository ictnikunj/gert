<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Action;

use Shopware\Core\Content\Mail\Service\AbstractMailService;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\CmsExtensions\Form\Aggregate\FormGroupField\FormGroupFieldCollection;
use Swag\CmsExtensions\Form\Aggregate\FormGroupField\FormGroupFieldEntity;
use Swag\CmsExtensions\Form\Aggregate\FormGroupField\Type\Email;
use Swag\CmsExtensions\Form\Event\CustomFormEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FormMailSubscriber implements EventSubscriberInterface
{
    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * @var AbstractMailService
     */
    private $mailService;

    public function __construct(SystemConfigService $systemConfigService, AbstractMailService $mailService)
    {
        $this->systemConfigService = $systemConfigService;
        $this->mailService = $mailService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CustomFormEvent::EVENT_NAME => 'sendMail',
        ];
    }

    public function sendMail(CustomFormEvent $event): void
    {
        $receivers = $event->getForm()->getReceivers();

        if (empty($receivers)) {
            $receivers[] = $this->systemConfigService->get('core.basicInformation.email', $event->getSalesChannelId());
        }

        $groups = $event->getForm()->getGroups();
        $fields = $groups === null ? new FormGroupFieldCollection() : $groups->getFields();

        $mailTemplate = $event->getForm()->getMailTemplate();
        $data = $mailTemplate !== null ? $mailTemplate->jsonSerialize() : [];
        $data['salesChannelId'] = $event->getSalesChannelId();
        if ($sender = $this->getSenderMail($fields, $event->getFormData())) {
            $data['replyTo'] = $sender;
        }

        $templateData = [
            'form' => $event->getForm(),
            'fields' => $fields,
            'formData' => $event->getFormData(),
            'salesChannel' => $event->getSalesChannelContext()->getSalesChannel(),
        ];

        foreach ($receivers as $mail) {
            if (!\is_string($mail)) {
                continue;
            }

            $data['recipients'] = [$mail => $mail];

            $this->mailService->send($data, $event->getContext(), $templateData);
        }
    }

    private function getSenderMail(FormGroupFieldCollection $fieldCollection, array $formData): ?string
    {
        $mailField = $fieldCollection->filter(static function (FormGroupFieldEntity $field) {
            return $field->getType() === Email::NAME;
        })->first();

        if ($mailField === null) {
            return null;
        }

        return $formData[$mailField->getTechnicalName()] ?? null;
    }
}
