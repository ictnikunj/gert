<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Test\Form\Route;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Test\TestCaseBase\SalesChannelFunctionalTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Salutation\SalutationDefinition;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\CmsExtensions\Form\Action\FormMailSubscriber;
use Swag\CmsExtensions\Form\Aggregate\FormGroupField\FormGroupFieldCollection;
use Swag\CmsExtensions\Form\Event\CustomFormEvent;
use Swag\CmsExtensions\Form\FormDefinition;
use Swag\CmsExtensions\Form\FormEntity;
use Swag\CmsExtensions\Test\Mock\EventDispatcherMock;
use Swag\CmsExtensions\Test\Mock\MailServiceMock;
use Swag\CmsExtensions\Util\Lifecycle\FormDefaults;

class FormMailSubscriberTest extends TestCase
{
    use SalesChannelFunctionalTestBehaviour;

    public const VALID_MAIL_ADDRESS = 'valid@mail.com';
    public const SUBJECT = 'Subject';
    public const CONTENT_HTML = '<p>Hello World!</p>';
    public const CONTENT_PLAIN = 'Hello World!';

    /**
     * @var FormMailSubscriber
     */
    private $formMailSubscriber;

    /**
     * @var EntityRepositoryInterface
     */
    private $formRepository;

    /**
     * @var EventDispatcherMock
     */
    private $eventDispatcher;

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * @var MailServiceMock
     */
    private $mailService;

    public function setUp(): void
    {
        parent::setUp();
        $container = $this->getContainer();

        $this->formRepository = $container->get(\sprintf('%s.repository', FormDefinition::ENTITY_NAME));
        $this->systemConfigService = $container->get(SystemConfigService::class);
        $this->mailService = new MailServiceMock();
        $this->formMailSubscriber = new FormMailSubscriber(
            $this->systemConfigService,
            $this->mailService
        );
    }

    public function testSend(): void
    {
        $form = $this->createForm();
        $salesChannelContext = $this->createSalesChannelContext();

        $groups = $form->getGroups();
        $fields = $groups === null ? new FormGroupFieldCollection() : $groups->getFields();

        $formData = [
            'selectField' => 'good',
            'textField' => 'Demotext',
        ];

        $event = new CustomFormEvent($salesChannelContext, $form, $formData);

        $this->formMailSubscriber->sendMail($event);

        static::assertCount(2, $this->mailService->getSentMails());
        list($data, $context, $templateData) = \current($this->mailService->getSentMails());

        static::assertSame([self::VALID_MAIL_ADDRESS => self::VALID_MAIL_ADDRESS], $data['recipients']);
        static::assertSame(self::SUBJECT, $data['subject']);
        static::assertSame(self::CONTENT_HTML, $data['contentHtml']);
        static::assertSame(self::CONTENT_PLAIN, $data['contentPlain']);
        static::assertArrayNotHasKey('replyTo', $data);
        static::assertSame($salesChannelContext->getSalesChannelId(), $data['salesChannelId']);
        static::assertSame($salesChannelContext->getContext(), $context);

        static::assertSame($form, $templateData['form']);
        static::assertSame($salesChannelContext->getSalesChannel(), $templateData['salesChannel']);
        static::assertSame($formData, $templateData['formData']);
        static::assertEquals($fields, $templateData['fields']);
    }

    public function testSendWithoutRecipient(): void
    {
        $form = $this->createForm();
        $salesChannelContext = $this->createSalesChannelContext();

        $groups = $form->getGroups();
        $fields = $groups === null ? new FormGroupFieldCollection() : $groups->getFields();

        $formData = [
            'selectField' => 'good',
            'textField' => 'Demotext',
        ];

        $form->setReceivers(null);

        $event = new CustomFormEvent($salesChannelContext, $form, $formData);

        $this->formMailSubscriber->sendMail($event);

        static::assertCount(1, $this->mailService->getSentMails());
        list($data, $context, $templateData) = \current($this->mailService->getSentMails());

        $recipient = $this->systemConfigService->get('core.basicInformation.email', $event->getSalesChannelId());
        static::assertIsString($recipient);
        static::assertSame([$recipient => $recipient], $data['recipients']);
        static::assertSame(self::SUBJECT, $data['subject']);
        static::assertSame(self::CONTENT_HTML, $data['contentHtml']);
        static::assertSame(self::CONTENT_PLAIN, $data['contentPlain']);
        static::assertArrayNotHasKey('replyTo', $data);
        static::assertSame($salesChannelContext->getSalesChannelId(), $data['salesChannelId']);
        static::assertSame($salesChannelContext->getContext(), $context);

        static::assertSame($form, $templateData['form']);
        static::assertSame($salesChannelContext->getSalesChannel(), $templateData['salesChannel']);
        static::assertSame($formData, $templateData['formData']);
        static::assertEquals($fields, $templateData['fields']);
    }

    public function testSendWithMailField(): void
    {
        $form = $this->createForm();
        $salesChannelContext = $this->createSalesChannelContext();

        $groups = $form->getGroups();
        $fields = $groups === null ? new FormGroupFieldCollection() : $groups->getFields();

        $formData = [
            'selectField' => 'good',
            'textField' => 'Demotext',
            'emailField' => self::VALID_MAIL_ADDRESS,
        ];

        $event = new CustomFormEvent($salesChannelContext, $form, $formData);

        $this->formMailSubscriber->sendMail($event);

        static::assertCount(2, $this->mailService->getSentMails());
        list($data, $context, $templateData) = \current($this->mailService->getSentMails());

        static::assertSame([self::VALID_MAIL_ADDRESS => self::VALID_MAIL_ADDRESS], $data['recipients']);
        static::assertSame(self::SUBJECT, $data['subject']);
        static::assertSame(self::CONTENT_HTML, $data['contentHtml']);
        static::assertSame(self::CONTENT_PLAIN, $data['contentPlain']);
        static::assertSame(self::VALID_MAIL_ADDRESS, $data['replyTo']);
        static::assertSame($salesChannelContext->getSalesChannelId(), $data['salesChannelId']);
        static::assertSame($salesChannelContext->getContext(), $context);

        static::assertSame($form, $templateData['form']);
        static::assertSame($salesChannelContext->getSalesChannel(), $templateData['salesChannel']);
        static::assertSame($formData, $templateData['formData']);
        static::assertEquals($fields, $templateData['fields']);
    }

    public function testSubscribedEvents(): void
    {
        static::assertSame([CustomFormEvent::EVENT_NAME => 'sendMail'], FormMailSubscriber::getSubscribedEvents());
    }

    private function createForm(bool $loadAll = false): FormEntity
    {
        $id = Uuid::randomHex();
        $context = Context::createDefaultContext();

        $this->formRepository->upsert([
            [
                'id' => $id,
                'title' => 'Formtitel',
                'technicalName' => 'technical-form-name',
                'successMessage' => 'Success!',
                'mailTemplate' => [
                    'mailTemplateTypeId' => FormDefaults::FORM_MAIL_TEMPLATE_TYPE_ID,
                    'subject' => self::SUBJECT,
                    'contentHtml' => self::CONTENT_HTML,
                    'contentPlain' => self::CONTENT_PLAIN,
                ],
                'receivers' => [
                    self::VALID_MAIL_ADDRESS,
                    'valid@as-well.com',
                ],
                'groups' => [
                    [
                        'position' => 0,
                        'technicalName' => 'row1',
                        'title' => 'Zeile 1',
                        'fields' => [
                            [
                                'position' => 0,
                                'width' => 6,
                                'type' => 'text',
                                'required' => true,
                                'technicalName' => 'textField',
                                'label' => 'Name',
                            ],
                            [
                                'position' => 1,
                                'width' => 6,
                                'type' => 'select',
                                'required' => true,
                                'technicalName' => 'selectSalutationField',
                                'label' => 'Salutation',
                                'config' => [
                                    'entity' => SalutationDefinition::ENTITY_NAME,
                                ],
                            ],
                            [
                                'position' => 0,
                                'width' => 6,
                                'type' => 'text',
                                'required' => true,
                                'placeholder' => 'will not be filled',
                                'technicalName' => 'missingTextField',
                                'label' => 'Name',
                            ],
                        ],
                    ],
                    [
                        'position' => 1,
                        'technicalName' => 'row2',
                        'fields' => [
                            [
                                'position' => 0,
                                'width' => 8,
                                'type' => 'email',
                                'required' => true,
                                'technicalName' => 'emailField',
                                'label' => 'Sender Mail',
                            ],
                        ],
                    ],
                ],
            ],
        ], $context);

        $criteria = new Criteria([$id]);
        $criteria
            ->addAssociation('groups.fields')
            ->addAssociation('mailTemplate')
            ->getAssociation('groups')
            ->addSorting(new FieldSorting('position'))
            ->getAssociation('fields')
            ->addSorting(new FieldSorting('position'));

        /** @var FormEntity|null $form */
        $form = $this->formRepository->search($criteria, $context)->first();

        static::assertNotNull($form);

        return $form;
    }
}
