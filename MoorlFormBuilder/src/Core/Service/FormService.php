<?php

namespace MoorlFormBuilder\Core\Service;

use MoorlFormBuilder\Core\Content\FormAppointment\FormAppointmentCollection;
use MoorlFormBuilder\Core\Content\FormAppointment\FormAppointmentDefinition;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Doctrine\DBAL\Connection;
use MoorlFormBuilder\Core\Content\Form\FormCollection;
use MoorlFormBuilder\Core\Content\Form\FormEntity;
use MoorlFormBuilder\Core\Event\AutocompleteCriteriaEvent;
use MoorlFormBuilder\Core\Event\CmsFormEvent;
use MoorlFormBuilder\Core\Event\FormCriteriaEvent;
use MoorlFormBuilder\Core\Event\FormFireEvent;
use MoorlFormBuilder\Core\Event\FormLoadEvent;
use MoorlFormBuilder\Core\Event\FormOptionCriteriaEvent;
use MoorlFormBuilder\MoorlFormBuilder;
use MoorlFoundation\Core\PluginHelpers;
use Shopware\Core\Content\Media\MediaCollection;
use Shopware\Core\Framework\Adapter\Translation\Translator;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Grouping\FieldGrouping;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Event\EventData\MailRecipientStruct;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Framework\Captcha\AbstractCaptcha;
use Shopware\Storefront\Framework\Captcha\Exception\CaptchaInvalidException;
use Shopware\Storefront\Framework\Routing\RequestTransformer;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class FormService
{
    protected Criteria $formCriteria;
    protected StorefrontMediaUploader $mediaUploader;
    protected ?array $blacklist;
    private Context $context;
    private ?SalesChannelContext $salesChannelContext = null;
    private DefinitionInstanceRegistry $definitionInstanceRegistry;
    private NewsletterService $newsletterService;
    private SystemConfigService $systemConfigService;
    private Connection $connection;
    private EventDispatcherInterface $eventDispatcher;
    private EntityRepositoryInterface $formRepo;
    private array $whitelist;
    private ?string $localeCode = null;
    private ?string $formId = null;
    private ?FormEntity $form = null;
    private ?FormCollection $forms = null;
    private ?Session $session = null;
    private array $violations = [];
    private RequestStack $requestStack;
    private bool $checkCache = false;
    /**
     * @var iterable<AbstractCaptcha>
     */
    private iterable $captchas;
    private Translator $translator;

    public function __construct(
        NewsletterService $newsletterService,
        DefinitionInstanceRegistry $definitionInstanceRegistry,
        Connection $connection,
        SystemConfigService $systemConfigService,
        EventDispatcherInterface $eventDispatcher,
        EntityRepositoryInterface $formRepo,
        StorefrontMediaUploader $mediaUploader,
        RequestStack $requestStack,
        iterable $captchas,
        array $allowedExtensions,
        Translator $translator
    )
    {
        $this->newsletterService = $newsletterService;
        $this->definitionInstanceRegistry = $definitionInstanceRegistry;
        $this->connection = $connection;
        $this->systemConfigService = $systemConfigService;
        $this->eventDispatcher = $eventDispatcher;
        $this->formRepo = $formRepo;
        $this->mediaUploader = $mediaUploader;
        $this->requestStack = $requestStack;
        $this->whitelist = $allowedExtensions;
        $this->captchas = $captchas;
        $this->translator = $translator;

        $this->context = Context::createDefaultContext();
    }

    /**
     * @param FormEntity|null $form
     */
    public function setForm(?FormEntity $form): void
    {
        $this->form = $form;
    }

    /**
     * @param bool $checkCache
     */
    public function setCheckCache(bool $checkCache): void
    {
        $this->checkCache = $checkCache;
    }

    public function setFormElementsOptions(?Criteria $mainCriteria = null): void
    {
        foreach ($this->form->getFormElements() as $formElement) {
            if ($formElement->getType() === 'appointment') {
                $timeInterval = new \DateInterval(sprintf("PT%sM", $formElement->getTimeStep()));
                $timeMin = new \DateTime();
                $timeMax = clone($timeMin);
                $timeRange = new \DatePeriod(
                    $timeMin->setTime($formElement->getTimeMin(),0),
                    $timeInterval,
                    $timeMax->setTime($formElement->getTimeMax(),0)
                );

                $dateInterval = new \DateInterval(sprintf("P%sD", $formElement->getDateStep()));
                $dateMin = (new \DateTime($formElement->getDateMin()))->setTime(0,0);
                $dateMax = (new \DateTime($formElement->getDateMax()))->setTime(23,59);
                $dateRange = new \DatePeriod($dateMin, $dateInterval, $dateMax);

                if ($mainCriteria) {
                    $criteria = clone $mainCriteria;
                } else {
                    $criteria = new Criteria();
                }

                $criteria->addFilter(new EqualsFilter('active', true));
                $criteria->addFilter(new EqualsFilter('formElement', $formElement->getName()));
                $criteria->addFilter(new EqualsFilter('formId', $this->form->getId()));
                $criteria->addFilter(new RangeFilter('start', [
                    'lte' => $dateMax->format(DATE_ATOM),
                    'gte' => $dateMin->format(DATE_ATOM),
                ]));
                $appointmentRepository = $this->definitionInstanceRegistry->getRepository(FormAppointmentDefinition::ENTITY_NAME);
                /** @var FormAppointmentCollection $appointments */
                $appointments = $appointmentRepository->search($criteria, $this->getContext())->getEntities();

                $optgroup = [];
                foreach ($dateRange as $date) {
                    if (in_array($date->format("w"), $formElement->getDateExclude())) {
                        continue;
                    }

                    $options = [];
                    foreach ($timeRange as $time) {
                        $value = sprintf("%sT%s", $date->format('Y-m-d'), $time->format('H:i'));

                        $options[] = [
                            'value' => $value,
                            'blocked' => $appointments->isBlocked($value)
                        ];
                    }

                    $optgroup[] = [
                        'label' => $date->format('Y-m-d'),
                        'options' => $options,
                    ];
                }

                $formElement->setOptions($optgroup);
            }
        }
    }

    public function disableAppointment(array $keyValues = []): void
    {
        $criteria = new Criteria();
        foreach ($keyValues as $field => $keyValue) {
            $criteria->addFilter(new EqualsFilter($field, $keyValue));
        }

        $appointmentRepository = $this->definitionInstanceRegistry->getRepository(FormAppointmentDefinition::ENTITY_NAME);
        $ids = $appointmentRepository->searchIds($criteria, $this->getContext())->getIds();

        $payload = array_map(function ($id) {
            return [
                'active' => false,
                'id' => $id
            ];
        }, $ids);

        $appointmentRepository->upsert($payload, $this->getContext());
    }

    public function addAppointment(string $formId, ?array $userValues = null, array $keyValues = []): void
    {
        if (!$userValues) {
            $userValues = $this->form->getUserValues();
            if (!$userValues) {
                return;
            }
        }

        $form = $this->getFormById($formId);
        $payload = [];

        foreach ($form->getFormElements() as $formElement) {
            if ($formElement->getType() === 'appointment') {
                if (!empty($userValues[$formElement->getName()])) {
                    $payload[] = array_merge([
                        'id' => Uuid::randomHex(),
                        'active' => true,
                        'formElement' => $formElement->getName(),
                        'formId' => $formId,
                        'start' => $userValues[$formElement->getName()]
                    ], $keyValues);
                }
            }
        }

        if (empty($payload)) {
            return;
        }

        $appointmentRepository = $this->definitionInstanceRegistry->getRepository(FormAppointmentDefinition::ENTITY_NAME);
        $appointmentRepository->upsert($payload, $this->getContext());
    }

    public function fire(Context $context, SalesChannelContext $salesChannelContext = null): ?array
    {
        $this->setContext($context);
        $this->setSalesChannelContext($salesChannelContext);
        $this->initForm();

        if ($this->hasViolations()) {
            return $this->getViolations();
        }

        if ($this->getSalesChannelContext()) {
            $salesChannelId = $this->getSalesChannelContext()->getSalesChannel()->getId();
            $email = $this->systemConfigService->get('core.basicInformation.email', $salesChannelId);
            $blacklist = $this->systemConfigService->get('MoorlFormBuilder.config.blacklist', $salesChannelId);
        } else {
            $email = $this->systemConfigService->get('core.basicInformation.email');
            $blacklist = $this->systemConfigService->get('MoorlFormBuilder.config.blacklist');
        }

        $this->cacheUserData($email);

        if ($this->getFormId()) {
            // example: add to cart item has no form id and no data
            $this->checkCaptcha();
            $this->setBlacklist($blacklist);
            $this->validateUserData();
            $this->validateFiles();
            if ($this->hasViolations()) {
                return $this->getViolations();
            }
            $this->uploadFiles();
            if ($this->hasViolations()) {
                return $this->getViolations();
            }
        }

        $this->setCurrentFormPayload();
        $this->setCurrentFormSummaryHTML();
        $this->setCurrentFormSummaryPlain();

        return null;
    }

    public function initForm(): bool
    {
        if (!$this->form) {
            if (!$this->getFormId()) {
                $this->violations[] = $this->trans('moorl-form-builder.general.internalError');
            } else {
                $this->initCurrentForm($this->getFormId());

                if (!$this->form) {
                    $this->violations[] = $this->trans('moorl-form-builder.general.internalError');
                }
            }
        }
        return $this->hasViolations();
    }

    public function getFormId(): ?string
    {
        if (!$this->formId) {
            $this->formId = $this->requestStack->getCurrentRequest()->get('_form_id');
        }

        return $this->formId;
    }

    public function setFormId(string $formId): void
    {
        $this->formId = $formId;
    }

    public function trans(string $snippet, ?array $data = []): array
    {
        return [
            'snippet' => $snippet,
            'data' => $data
        ];
    }

    public function initCurrentForm(?string $formId = null)
    {
        $this->setCurrentForm($formId);
        $this->_initCurrentForm();
    }

    public function setCurrentForm(?string $formId = null)
    {
        if ($formId || $this->getFormId()) {
            if ($formId) {
                $this->setFormId($formId);
            }
            if (!$this->forms || !$this->forms->get($this->getFormId())) {
                $this->setFormById($this->getFormId());
            }
            $this->form = $this->forms->get($this->getFormId());
        } else {
            if (!$this->forms || !$this->forms->last()) {
                $this->setForms(); // context and criteria need to be set
            }
            $this->form = $this->forms->last(); // get last inserted form
        }
    }

    public function setFormById(string $formId): void
    {
        $this->setFormCriteria(new Criteria([$formId]));
        $this->setForms();
    }

    private function _initCurrentForm(): void
    {
        $this->getLocaleCode();

        if ($this->form) {
            if ($this->form->getInitialized()) {
                return;
            }

            $this->addEntitySelections();
            $event = new FormLoadEvent($this->getContext(), $this->form);
            $this->eventDispatcher->dispatch($event);
            $this->setFormDataFromSession();
            $this->setFormDataFromCustomer();
            $this->setCurrentFormPayload();
            $this->setCurrentFormSummaryHTML();
            $this->setCurrentFormSummaryPlain();
            $this->setFormElementsOptions();

            $this->form->setInitialized(true);
            $this->forms->add($this->form);
        }
    }

    public function getLocaleCode(): string
    {
        if (!$this->localeCode && $this->getContext()) {
            $repository = $this->definitionInstanceRegistry->getRepository('language');
            $criteria = new Criteria([$this->getContext()->getLanguageId()]);
            $criteria->addAssociation('locale');
            $this->localeCode = $repository->search($criteria, $this->getContext())->first()->getLocale()->getCode() ?: 'en-GB';
        }
        return $this->localeCode ?: 'en-GB';
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function setContext(Context $context)
    {
        $this->context = $context;
    }

    private function addEntitySelections(): void
    {
        $locale = $this->getLocaleCode();

        /* Set translations FormEntity */
        $this->form->setTranslated([
            'label' => $this->getLocalVariable($this->form->getLabel()),
            'successMessage' => $this->getLocalVariable($this->form->getSuccessMessage()),
            'submitText' => $this->getLocalVariable($this->form->getSubmitText()),
        ]);

        $elements = $this->form->getData();

        foreach ($elements as &$element) {
            if ($this->getIsSet($element, 'isEntitySelect')) {
                $repository = $this->definitionInstanceRegistry->getRepository($element['entitySelect']['relatedEntity']);

                $criteria = new Criteria();

                // Product Media Hack
                if ($element['entitySelect']['relatedEntity'] == 'product') {
                    $criteria->addAssociation('cover');
                }

                $criteria->addSorting(new FieldSorting($element['entitySelect']['labelProperty'], FieldSorting::ASCENDING));

                $event = new FormOptionCriteriaEvent($this->getContext(), $criteria, $element, $this->form->getAction());
                $this->eventDispatcher->dispatch($event);

                $collection = $repository->search($criteria, $this->getContext())->getEntities();

                $options = [];

                foreach ($collection as $entity) {
                    $mediaId = null;

                    if ($this->getIsSet($element, 'useImageSelection')) {
                        // Product Media Hack
                        if ($element['entitySelect']['relatedEntity'] == 'product' && $entity->getCover()) {
                            $mediaId = $entity->getCover()->getMedia()->getId();
                        } else {
                            $mediaId = $entity->get($element['entitySelect']['mediaProperty']);
                        }
                    }

                    try {
                        if (isset($entity->getTranslated()[$element['entitySelect']['labelProperty']])) {
                            $label = $entity->getTranslated()[$element['entitySelect']['labelProperty']];
                        } else {
                            $label = $entity->get($element['entitySelect']['labelProperty']);
                        }
                    } catch (\Exception $exception) {
                    }

                    $options[] = [
                        'id' => $entity->get($element['entitySelect']['valueProperty']),
                        'value' => $entity->get($element['entitySelect']['valueProperty']),
                        'label' => [
                            $locale => $label
                        ],
                        'entity' => $entity, // set complete entity for customizing
                        'mediaId' => $mediaId,
                        'useTrans' => null,
                        'emailReceiver' => null,
                    ];
                }

                $element['options'] = $options;
            }

            /* Set translations FormEntity - Element */
            $element['translated'] = [
                'label' => $this->getLocalVariable($this->getIsSet($element, 'label')),
                'placeholder' => $this->getLocalVariable($this->getIsSet($element, 'placeholder')),
            ];

            /* Set translations FormEntity - Element options */
            foreach ($element['options'] as &$option) {
                $option['translated'] = [
                    'label' => $this->getLocalVariable($this->getIsSet($option, 'label')),
                    'customField1' => $this->getLocalVariable($this->getIsSet($option, 'customField1')),
                    'customField2' => $this->getLocalVariable($this->getIsSet($option, 'customField2')),
                    'customField3' => $this->getLocalVariable($this->getIsSet($option, 'customField3')),
                ];
            }
        }

        $this->form->setData($elements);
    }

    public function getLocalVariable(?array $var): ?string
    {
        if (isset($var[$this->localeCode])) {
            return $var[$this->localeCode];
        } elseif (is_array($var) && count($var) > 0) {
            return reset($var);
        } else {
            return null;
        }
    }

    public function getIsSet(?array $var, string $index, $alt = null)
    {
        if (isset($var[$index])) {
            return $var[$index];
        } else {
            return $alt;
        }
    }

    public function setFormDataFromCustomer(): void
    {
        if ($this->form->getRelatedEntity() !== 'customer') {
            return;
        }

        if (!$this->getSalesChannelContext()) {
            return;
        }

        $customer = $this->getSalesChannelContext()->getCustomer();
        if (!$customer) {
            return;
        }
        $customerCustomFields = $customer->getCustomFields();

        $formElements = $this->form->getData();

        foreach ($formElements as &$formElement) {
            if ($formElement['mapping']) {
                $mapping = explode('.', $formElement['mapping']);
                if ($mapping[0] === 'customFields') {
                    $formElement['value'] = $this->getIsSet($customerCustomFields, $mapping[1]);
                } else {
                    $formElement['value'] = $customer->get($mapping[0]);
                }
            }
        }

        $this->form->setData($formElements);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customerId', $customer->getId()));
        $this->setFormElementsOptions($criteria);
    }

    public function setFormDataFromSession(): void
    {
        if ($this->checkCache && getenv("SHOPWARE_HTTP_CACHE_ENABLED") === "1") {
            return; // prevent prefill when cache turned on
        }

        if ($this->checkCache && $this->systemConfigService->get('MoorlFormBuilder.config.disableCache')) {
            return; // prevent prefill by config
        }

        $this->initSession();

        $userValues = $this->session->get('moorl-form-builder.values_' . $this->form->getId());
        if (!$userValues) {
            return;
        }

        $formElements = $this->form->getData();

        foreach ($formElements as &$formElement) {
            if ($userValue = $this->getIsSet($userValues, $formElement['name'])) {
                $formElement['value'] = $userValue;
            }
        }

        $this->form->setData($formElements);
    }

    public function initSession(): void
    {
        if (!$this->session) {
            $this->session = new Session();
        }
    }

    public function validateTaxIdNumber(string $taxIdNumber): bool
    {
        try {
            $vatid = preg_replace("/[^a-zA-Z0-9]]/", "", $taxIdNumber);
            $vatidRegex = "/^[a-z]{2}[a-z0-9]{0,12}$/i";

            if (preg_match($vatidRegex, $vatid) !== 1) {
                throw new \Exception('Invalid Vat Id Format');
            }

            $client = new \SoapClient("https://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl");

            $params = [
                'countryCode' => substr($vatid, 0, 2),
                'vatNumber' => substr($vatid, 2),
            ];
            $result = $client->checkVatApprox($params);

            if ($result->valid) {
                return true;
            } else {
                return false;
            }

        } catch (\Exception $exception) {
            return false;
        }
    }

    public function setCurrentFormSummaryPlain()
    {
        $medias = $this->getMediaCollection();
        $html = "\n";
        foreach ($this->form->getData() as $formElement) {
            if ($formElement['value'] || in_array($formElement['type'], ['checkbox', 'switch'])) {
                $html .= $formElement['translated']['label'] . " ";
                switch ($formElement['type']) {
                    case 'datepicker':
                        $html .= $formElement['value'];
                        break;
                    case 'upload':
                        $media = $medias->get($formElement['value']);
                        if ($media) {
                            $html .= $media->getUrl();
                        }
                        break;
                    case 'select':
                    case 'multiselect':
                    case 'radio-group':
                    case 'checkbox-group':
                        foreach ($formElement['options'] as $option) {
                            if ($option['value'] == $formElement['value'] || (is_array($formElement['value']) && in_array($option['value'], $formElement['value']))) {
                                $html .= $option['translated']['label'] . " ";
                                if (isset($option['priceCalculation']) && is_numeric($option['priceCalculation'])) {
                                    // Todo: get the currency here!?
                                    $html .= number_format($option['priceCalculation'], 2, ',', '.');
                                }
                            } elseif (isset($formElement['value'][$option['value']])) {
                                $html .= $option['translated']['label'] . ': ' . $formElement['value'][$option['value']] . ' ';
                            }
                        }
                        break;
                    case 'repeater-open':
                        $html .= $this->form->getRepeaterDataValue($formElement['name']);
                        break;
                    case 'checkbox':
                    case 'switch':
                        $html .= empty($formElement['value']) ? $this->translator->trans("moorl-form-builder.general.no") : $this->translator->trans("moorl-form-builder.general.yes");
                        break;
                    default:
                        $html .= $formElement['value'];
                }
                $html .= "\n";
            }
        }

        $this->form->setSummaryPlain($html);
    }

    public function setCurrentFormSummaryHTML()
    {
        $medias = $this->getMediaCollection();
        $html = '<table border="0" style="font-size: 1em; margin-bottom: 5px">';
        foreach ($this->form->getData() as $formElement) {
            $addHtml = '';
            if ($formElement['value'] || in_array($formElement['type'], ['checkbox', 'switch'])) {
                $html .= '<tr><td bgcolor="#F7F7F2" style="border-bottom:1px solid #cccccc;">' . $formElement['translated']['label'] . '</td>';
                if ($this->getIsSet($formElement, 'usePriceCalculation')) {
                    $cols = 1;
                } else {
                    $cols = 2;
                }
                $html .= '<td colspan="' . $cols . '" style="border-bottom:1px solid #cccccc;">';
                switch ($formElement['type']) {
                    case 'datepicker':
                        $html .= $formElement['value'];
                        break;
                    case 'upload':
                        $media = $medias->get($formElement['value']);
                        if ($media) {
                            if (in_array($media->getFileExtension(), ['png', 'jpg', 'jpeg', 'gif'])) {
                                $html .= '<a href="' . $media->getUrl() . '"><img style="width: 100px;" src="' . $media->getUrl() . '" alt="' . $media->getFileName() . '"></a>';
                            } else {
                                $html .= '<a href="' . $media->getUrl() . '">' . $formElement['translated']['label'] . '</a>';
                            }
                        }
                        break;
                    case 'appointment':
                        $appointment=  new \DateTime($formElement['value']);
                        $html .= $appointment->format($this->translator->trans("moorl-form-builder.general.dateTimeFormat"));
                        break;
                    case 'select':
                    case 'multiselect':
                    case 'radio-group':
                    case 'checkbox-group':
                        $addHtml = '<td align="right" style="border-bottom:1px solid #cccccc;">';
                        foreach ($formElement['options'] as $option) {
                            if ($option['value'] == $formElement['value'] || (is_array($formElement['value']) && in_array($option['value'], $formElement['value']))) {
                                $html .= $option['translated']['label'] . '<br>';
                                if (isset($option['priceCalculation']) && is_numeric($option['priceCalculation'])) {
                                    // Todo: get the currency here!?
                                    $addHtml .= number_format($option['priceCalculation'], 2, ',', '.') . '<br>';
                                }
                            } elseif (isset($formElement['value'][$option['value']])) {
                                $html .= $option['translated']['label'] . ': ' . $formElement['value'][$option['value']] . '<br>';
                            }
                        }
                        $addHtml .= '</td>';
                        break;
                    case 'repeater-open':
                        $html .= nl2br($this->form->getRepeaterDataValue($formElement['name']));
                        break;
                    case 'checkbox':
                    case 'switch':
                        $html .= empty($formElement['value']) ? $this->translator->trans("moorl-form-builder.general.no") : $this->translator->trans("moorl-form-builder.general.yes");
                        break;
                    default:
                        $html .= nl2br(trim(str_replace(["'", "\""], "&quot;", $formElement['value'])));
                }
                $html .= '</td>' . ($cols == 1 ? $addHtml : '') . '</tr>';
            }
        }
        $html .= '</table>';
        $html = strip_tags($html, '<table><tr><td><br><img><a>');
        $this->form->setSummaryHTML($html);
    }

    public function getMediaCollection(?array $mediaIds = null): ?MediaCollection
    {
        if ($this->form->getMedias()) {
            return $this->form->getMedias();
        }

        if (!$mediaIds) {
            $mediaIds = [];
            foreach ($this->form->getData() as $formElement) {
                if ($formElement['value'] && $formElement['type'] == 'upload') {
                    $mediaIds[] = $formElement['value'];
                }
            }
        }

        if (count($mediaIds) > 0) {
            $repo = $this->definitionInstanceRegistry->getRepository('media');
            $this->form->setMedias($repo->search(new Criteria($mediaIds), $this->getContext())->getEntities());
        }

        return $this->form->getMedias();
    }

    public function setCurrentFormPayload(): void
    {
        $payload = [];

        foreach ($this->form->getData() as $formElement) {
            if ($formElement['name'] && $formElement['mapping'] && $formElement['value']) {
                if (!empty($formElement['value'])) {
                    if ($formElement['value'] === 'true') {
                        $payload[$formElement['mapping']] = true;
                    } elseif ($formElement['value'] === 'false') {
                        $payload[$formElement['mapping']] = false;
                    } else {
                        if (in_array($formElement['type'], ['switch','checkbox'])) {
                            $payload[$formElement['mapping']] = true;
                        } else {
                            $payload[$formElement['mapping']] = $formElement['value'];
                        }
                    }
                }
            }
            $formElements[] = $formElement;
        }
        PluginHelpers::getNestedVar($payload);

        $this->form->setPayload($payload);
    }

    public function hasViolations(): bool
    {
        return count($this->violations) > 0 ? true : false;
    }

    public function getViolations(): array
    {
        return $this->violations;
    }

    /**
     * @return SalesChannelContext|null
     */
    public function getSalesChannelContext(): ?SalesChannelContext
    {
        return $this->salesChannelContext;
    }

    /**
     * @param SalesChannelContext|null $salesChannelContext
     */
    public function setSalesChannelContext(?SalesChannelContext $salesChannelContext): void
    {
        $this->salesChannelContext = $salesChannelContext;

        if ($salesChannelContext) {
            $this->context = $salesChannelContext->getContext();
        }
    }

    public function cacheUserData($fallbackReceiver = null): bool
    {
        $data = $this->requestStack->getCurrentRequest();

        $receivers = $this->form->getEmailReceiver() ? explode(";", $this->form->getEmailReceiver()) : ($fallbackReceiver ? [$fallbackReceiver] : []);
        $customerEmailReceiver = [];

        $userValues = [
            'storefrontUrl' => $data->attributes->get(RequestTransformer::STOREFRONT_URL)
        ];
        $formElements = [];

        foreach ($this->form->getData() as $formElement) {
            $requestValue = $data->get($formElement['name']);
            if ($formElement['name'] && $requestValue) {
                if (!empty($requestValue)) {
                    if ($requestValue instanceof RequestDataBag) {
                        $requestValue = $requestValue->all();
                    }

                    $userValues[$formElement['name']] = $requestValue;
                    $formElement['value'] = $requestValue;

                    if ($formElement['type'] == 'email' && ($this->form->getSendCopyType() === 'always' || $data->get('_send_copy'))) {
                        $customerEmailReceiver[] = $requestValue;
                    }

                    if ($formElement['type'] == 'email') {
                        $this->form->setReplyTo($requestValue);
                    }

                    if ($this->getIsSet($formElement, 'hasEmailReceiver') && is_array($formElement['options'])) {
                        foreach ($formElement['options'] as $option) {
                            if ($this->getIsSet($option, 'emailReceiver')) {
                                if (is_array($requestValue)) {
                                    if (in_array($option['value'], $requestValue)) {
                                        $receivers = array_merge(
                                            $receivers,
                                            explode(";", $option['emailReceiver'])
                                        );
                                    }
                                } else {
                                    if ($option['value'] == $requestValue) {
                                        $receivers = array_merge(
                                            $receivers,
                                            explode(";", $option['emailReceiver'])
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $formElements[] = $formElement;
        }

        $this->form->setEmailReceiver(implode(";", $receivers));
        $this->form->setCustomerEmailReceiver(implode(";", $customerEmailReceiver));
        $this->form->setData($formElements);
        $this->form->setUserValues($userValues);

        $this->addSessionUserValues($userValues);

        return $this->hasViolations();
    }

    public function addSessionUserValues(array $userValues = [], ?string $id = null): void
    {
        $this->initSession();
        if ($id) {
            $id = $id . $this->form->getId();
        } else {
            $id = $this->form->getId();
        }
        $userValues = array_merge($this->form->getUserValues(), $userValues);
        $this->session->set('moorl-form-builder.values_' . $id, $userValues);
    }

    public function checkCaptcha(): bool
    {
        $this->initSession();

        if ($this->form->getUseCaptcha()) {
            $activeCaptchas = $this->systemConfigService->get('core.basicInformation.activeCaptchasV2');

            foreach ($this->captchas as $captcha) {
                $captchaConfig = $activeCaptchas[$captcha->getName()] ?? [];
                $request = $this->requestStack->getCurrentRequest();

                if (!$captcha->supports($request, $captchaConfig)) {
                    continue;
                }

                if (!$captcha->isValid($request, $captchaConfig)) {
                    if ($captcha->shouldBreak()) {
                        throw new CaptchaInvalidException($captcha);
                    }

                    $this->violations[] = $this->trans('moorl-form-builder.general.captchaError');
                }
            }
        }

        return $this->hasViolations();
    }

    public function setBlacklist(?string $blacklist): void
    {
        if ($blacklist) {
            $blacklist = explode(',', $blacklist);
            $blacklist = array_map('trim', $blacklist);
            $blacklist = array_map('mb_strtolower', $blacklist);

            $this->blacklist = $blacklist;
        }
    }

    public function validateUserData(): bool
    {
        $data = $this->requestStack->getCurrentRequest();
        $formRepeaterName = null;

        foreach ($this->form->getData() as $formElement) {
            if ($formElement['type'] === 'repeater-open') {
                $formRepeaterName = $formElement['name'];
            }

            if ($formElement['type'] === 'repeater-close') {
                $formRepeaterName = null;
            }

            if ($formRepeaterName) {
                // Hotfix: Form elements within repeater will be validated client side
                continue;
            }

            $requestValue = $data->get($formElement['name']);
            if (!$requestValue) {
                continue;
            }

            if ($formElement['type'] === 'tax-id-number') {
                if (!$this->validateTaxIdNumber($requestValue)) {
                    $this->violations[] = $this->trans('moorl-form-builder.general.taxIdNumberError', [
                        '%value%' => $this->getIsSet($formElement['translated'], 'label', $formElement['name']),
                    ]);
                }
                continue;
            }

            if ($requestValue instanceof RequestDataBag) {
                $requestValue = $requestValue->all();
            }

            // Hotfix: Form elements with conditions will be validated client side
            $formElementConditions = (isset($formElement['conditions']) && count($formElement['conditions']) > 0);

            // Hotfix: Form elements with file uploads are set after the validation
            $formElementIsUpload = ($formElement['type'] === 'upload');

            if (!$requestValue && !$formElementConditions && !$formElementIsUpload && $formElement['required']) {
                $this->violations[] = $this->trans('moorl-form-builder.general.requiredError', [
                    '%value%' => $this->getIsSet($formElement['translated'], 'label', $formElement['name']),
                ]);
            }

            if (!empty($formElement['useBlacklist'])) {
                if ($requestValue && $this->inBlacklist($requestValue)) {
                    $this->violations[] = $this->trans('moorl-form-builder.general.blacklistError', [
                        '%value%' => $this->getIsSet($formElement['translated'], 'label', $formElement['name']),
                    ]);
                }
            }

            if (!empty($formElement['mapping']) && isset($formElement['isUnique']) && $requestValue) {
                if (!empty($requestValue) && $formElement['isUnique']) {
                    $repo = $this->definitionInstanceRegistry->getRepository($this->form->getRelatedEntity());
                    $criteria = new Criteria();
                    $criteria->addFilter(new EqualsFilter($formElement['mapping'], $requestValue));

                    if ($repo->search($criteria, $this->getContext())->first()) {
                        $this->violations[] = $this->trans('moorl-form-builder.general.duplicateError', [
                            '%value%' => $requestValue,
                        ]);
                    }
                }
            }
        }

        return $this->hasViolations();
    }

    public function inBlacklist($var): bool
    {
        if (!$this->blacklist || !is_array($this->blacklist)) {
            return false;
        }
        $vars = is_array($var) ? $var : [$var];
        foreach ($vars as $item) {
            foreach ($this->blacklist as $blacklistItem) {
                if (is_array($item)) {
                    return $this->inBlacklist($item);
                } else {
                    if (strpos(mb_strtolower($item), $blacklistItem) !== false) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * @param UploadedFile $file
     * @param string $technicalName
     *
     * Validates a single UploadedFile and add to binary attachment
     */
    private function validateFile(UploadedFile $file, string $technicalName): void
    {
        $formElement = $this->form->getFormElement($technicalName);
        if ($formElement->getType() !== 'upload') {
            return;
        }

        if (!$this->mediaUploader->validate($file, $formElement->getMediaType())) {
            $this->violations[] = $this->trans('moorl-form-builder.general.mediaTypeError', [
                '%name%' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                '%extension%' => $file->getClientOriginalExtension(),
                '%expected%' => $formElement->getMediaType()
            ]);
        }

        if (!$this->mediaUploader->checkSize($file)) {
            $this->violations[] = $this->trans('moorl-form-builder.general.mediaSizeError', [
                '%size%' => $this->mediaUploader->getFileSize(),
                '%maxSize%' => $this->mediaUploader->getMaxFileSize(),
            ]);
        }

        if ($this->hasViolations()) {
            return;
        }

        $formElement->setValue($file->getClientOriginalName());

        $this->form->addBinAttachment([
            'content' => file_get_contents($file->getPathname()),
            'fileName' => $file->getClientOriginalName(),
            'mimeType' => $file->getMimeType(),
        ]);
    }

    public function validateFiles(): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        $this->mediaUploader->setMaxFileSize($this->form->getMaxFileSize() * 1000000); // MB to Bytes

        foreach ($request->files as $name => $file) {
            if (is_array($file)) {
                foreach ($file as $repeaterItem) {
                    if (!is_array($repeaterItem)) {
                        continue;
                    }
                    foreach ($repeaterItem as $repeaterName => $repeaterFile) {
                        if ($repeaterFile instanceof UploadedFile) {
                            $this->validateFile($repeaterFile, $repeaterName);
                        }
                    }
                }
            } elseif ($file instanceof UploadedFile) {
                $this->validateFile($file, $name);
            }
        }

        return $this->hasViolations();
    }

    private function getAttachment(UploadedFile $file): array
    {
        return [
            'content' => file_get_contents($file->getPathname()),
            'fileName' => $file->getClientOriginalName(),
            'mimeType' => $file->getMimeType(),
        ];
    }

    public function uploadFiles(): bool
    {
        // Attachment only if sendMail active
        if ($this->form->getSendMail() && in_array($this->form->getType(), ['cms','snippet','customerRegister','productRequest'])) {
            return false;
        }

        $request = $this->requestStack->getCurrentRequest();
        $mediaIds = [];
        $formElements = [];
        $userValues = [];

        try {
            foreach ($this->form->getData() as $formElement) {
                if ($formElement['type'] == 'upload' && $file = $request->files->get($formElement['name'])) {
                    $mediaId = $this->mediaUploader->upload($file, $this->form->getMediaFolderId(), $this->getContext());
                    $formElement['value'] = $mediaId;
                    $userValues[$formElement['name']] = $mediaId;
                    $mediaIds[] = $mediaId;
                    $this->addSessionUserMedia($mediaId);
                }
                $formElements[] = $formElement;
            }
            $this->form->setData($formElements);

            if (count($mediaIds) > 0) {
                $this->getMediaCollection($mediaIds);
            }
        } catch (\Exception $exception) {
            $this->violations[] = $this->trans('moorl-form-builder.general.internalError');
            $this->violations[] = $this->trans($exception->getMessage());
        }

        $this->addSessionUserValues($userValues);
        $this->form->setUserValues(array_merge($this->form->getUserValues(), $userValues));

        return $this->hasViolations();
    }

    public function addSessionUserMedia(string $mediaId): void
    {
        $this->initSession();
        $media = $this->session->get('moorl-form-builder.media') ?: [];
        $media[] = $mediaId;
        $this->session->set('moorl-form-builder.media', $media);
    }

    public function setFormDataFromEntity(Entity $entity): void
    {
        $formElements = $this->form->getData();

        foreach ($formElements as &$formElement) {
            if ($entity && isset($formElement['mapping']) && !empty($formElement['mapping'])) {
                try {
                    if (isset($entity->getTranslated()[$formElement['mapping']])) {
                        $formElement['value'] = $entity->getTranslated()[$formElement['mapping']];
                    } else {
                        $formElement['value'] = $entity->get($formElement['mapping']);
                    }
                } catch (\Exception $exception) {
                }
            }
        }

        $this->form->setData($formElements);

        $criteria = new Criteria();
        if ($entity instanceof ProductEntity) {
            $criteria->addFilter(new EqualsFilter('productId', $entity->getId()));
        }
        $this->setFormElementsOptions($criteria);
    }

    public function getFormCriteria(): Criteria
    {
        if (!$this->formCriteria && $this->getFormId()) {
            $this->setFormCriteria(new Criteria([$this->getFormId()]));
        }

        return $this->formCriteria;
    }

    public function setFormCriteria(Criteria $formCriteria): void
    {
        $this->formCriteria = $formCriteria;
    }

    public function getFormById(string $formId): ?FormEntity
    {
        return $this->forms->get($formId);
    }

    public function setFormByCriteria(Criteria $criteria): void
    {
        $criteria->setLimit(1);
        $criteria->addFilter(new EqualsFilter('active', true));

        $this->setFormCriteria($criteria);
        $this->setForms();
    }

    public function initCurrentFormByAction(string $action)
    {
        $this->form = $this->forms->getByAction($action);
        $this->_initCurrentForm();
    }

    public function initCurrentFormByProductId(string $productId)
    {
        $this->form = $this->forms->getByProductId($productId);
        $this->_initCurrentForm();
    }

    public function initCurrentFormByTypeProductId(string $type, ?string $productId = null)
    {
        $this->form = $this->forms->getByTypeProductId($type, $productId);
        $this->_initCurrentForm();
    }

    public function initCurrentFormByTypeProductProperty(string $type, string $property, $value)
    {
        $this->form = $this->forms->getByTypeProductProperty($type, $property, $value);
        $this->_initCurrentForm();
    }

    public function getForms(): ?FormCollection
    {
        return $this->forms;
    }

    public function setForms(): void
    {
        $criteria = $this->getFormCriteria();

        $event = new FormCriteriaEvent($this->getContext(), $criteria);
        $this->eventDispatcher->dispatch($event);

        $formCollection = $this->formRepo->search($criteria, $this->getContext())->getEntities();

        if ($this->forms instanceof FormCollection) {
            $this->forms->merge($formCollection);
        } else {
            $this->forms = $formCollection;
        }
    }

    public function initFormsByType(Context $context, string $type, $initializeDeep = true): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', 1));
        $criteria->addFilter(new EqualsFilter('type', $type));

        if ($this->getSalesChannelContext()) {
            $salesChannelId = $this->getSalesChannelContext()->getSalesChannelId();

            $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
                new EqualsFilter('salesChannelId', $salesChannelId),
                new EqualsFilter('salesChannelId', null)
            ]));
        }

        $criteria->addAssociation('products.children');
        /* BUGFIX v6.4.6.0 */
        $productsCriteria = $criteria->getAssociation('products');
        $productsCriteria->addFilter(new NotFilter(NotFilter::CONNECTION_OR, [
            new EqualsFilter('id', null)
        ]));

        $this->initForms($context, $criteria, $initializeDeep);
    }

    public function initFormsByTypeProductId(Context $context, string $type, ?string $productId = null, $initializeDeep = true): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', 1));
        $criteria->addFilter(new EqualsFilter('type', $type));

        if ($this->getSalesChannelContext()) {
            $salesChannelId = $this->getSalesChannelContext()->getSalesChannelId();

            $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
                new EqualsFilter('salesChannelId', $salesChannelId),
                new EqualsFilter('salesChannelId', null)
            ]));
        }

        $criteria->addAssociation('products.children');
        $productsCriteria = $criteria->getAssociation('products');
        $productsCriteria->addFilter(new EqualsFilter('id', $productId));

        $this->initForms($context, $criteria, $initializeDeep);
    }

    public function initForms(Context $context, Criteria $criteria, $initializeDeep = true): void
    {
        $this->setContext($context);
        $this->setFormCriteria($criteria);
        $this->setForms();

        if ($initializeDeep) {
            foreach ($this->forms as $form) {
                if (!$form->getInitialized()) {
                    $this->form = $form;
                    $this->_initCurrentForm();
                }
            }
        }
    }

    public function getPageReload(): bool
    {
        $data = $this->requestStack->getCurrentRequest()->get('_reload');
        return $data ? true : false;
    }

    public function unsetUserData(): void
    {
        $this->initSession();
        $this->session->remove('moorl-form-builder.values_' . $this->form->getId());
    }

    public function unsetUserDataByFormId(string $formId): void
    {
        $this->initSession();
        $this->session->remove('moorl-form-builder.values_' . $formId);
    }

    public function unsetAllUserData(): void
    {
        $this->initSession();
        foreach ($this->forms as $form) {
            $this->session->remove('moorl-form-builder.values_' . $form->getId());
        }
    }

    public function autocomplete(string $formElementId, $isArray = true): array
    {
        $query = $this->requestStack->getCurrentRequest()->query->get('q');

        if (strlen($query) < 3) {
            return [];
        }

        $this->initForm();

        $formElements = $this->form->getData();

        $formElements = array_values(array_filter($formElements, function ($v, $k) use ($formElementId) {
            return $v['id'] == $formElementId;
        }, ARRAY_FILTER_USE_BOTH));

        if (count($formElements) == 0) {
            return [];
        }

        $formElement = $formElements[0];

        if (isset($formElement['autocomplete']) && isset($formElement['autocomplete']['relatedEntity']) && isset($formElement['autocomplete']['property'])) {
            $relatedEntity = $formElement['autocomplete']['relatedEntity'];
            $property = $formElement['autocomplete']['property'];

            $repo = $this->definitionInstanceRegistry->getRepository($relatedEntity);

            $criteria = new Criteria();
            $criteria->setLimit(10);
            $criteria->addFilter(new ContainsFilter($property, $query));
            $criteria->addGroupField(new FieldGrouping($property));

            $event = new AutocompleteCriteriaEvent($this->getContext(), $criteria, $formElement, $this->form->getAction());
            $this->eventDispatcher->dispatch($event);

            $results = [];

            /* @var $entity Entity */
            if ($isArray) {
                foreach ($repo->search($criteria, $this->getContext())->getEntities() as $entity) {
                    $results[] = $entity->get($property);
                }
            } else {
                foreach ($repo->search($criteria, $this->getContext())->getEntities() as $entity) {
                    $results[$entity->getId()] = $entity->get($property);
                }
            }

            return $results;
        }

        return [];
    }

    public function removeUserMedia(string $mediaId): void
    {
        $customer = $this->salesChannelContext->getCustomer();

        if ($customer) {
            $customerCustomFields = $customer->getCustomFields();

            if (is_array($customerCustomFields)) {
                foreach ($customerCustomFields as $name => $value) {
                    if ($value === $mediaId) {
                        $this->mediaUploader->delete($mediaId, $this->getContext());

                        $payload = [
                            'id' => $customer->getId(),
                            'customFields' => [
                                $name => null
                            ]
                        ];

                        $repository = $this->definitionInstanceRegistry->getRepository('customer');
                        $repository->upsert([$payload], $this->context);

                        return;
                    }
                }
            }
        }

        $this->initSession();

        $media = $this->session->get('moorl-form-builder.media') ?: [];

        if (in_array($mediaId, $media)) {
            $this->mediaUploader->delete($mediaId, $this->getContext());
        } else {
            $this->violations[] = $this->trans('moorl-form-builder.general.internalError');
        }
    }

    public function fireForm()
    {
        $event = new FormFireEvent($this->getContext(), $this->form);
        $this->eventDispatcher->dispatch($event);

        if ($this->form->getSendMail()) {
            /* Send Mail to Shop Owner */
            $receivers = explode(";", $this->form->getEmailReceiver());
            $receivers = array_map('trim', $receivers);
            $receivers = array_unique($receivers);

            foreach ($receivers as $receiver) {
                $this->setMailEvent($receiver);
            }

            /* Send Mail to Customer */
            if ($this->form->getCustomerEmailReceiver() && !empty($this->form->getCustomerEmailReceiver())) {
                $customerMailTemplateId = $this->form->getCustomerMailTemplateId();
                if ($customerMailTemplateId) {
                    $this->form->setMailTemplateId($customerMailTemplateId);
                }

                $receivers = explode(";", $this->form->getCustomerEmailReceiver());
                $receivers = array_map('trim', $receivers);
                $receivers = array_unique($receivers);

                foreach ($receivers as $receiver) {
                    $this->setMailEvent($receiver);
                }
            }
        }

        if ($this->form->getInsertNewsletter()) {
            if ($this->requestStack->getCurrentRequest()->get('_insert_newsletter')) {
                $userValues = $this->form->getUserValues();
                $this->newsletterService->subscribe($userValues, $this->getSalesChannelContext(), false);
            }
        }

        if ($this->form->getInsertHistory()) {
            $this->insertHistory();
        }

        if ($this->form->getInsertDatabase()) {
            $this->insertDatabase();
        }
    }

    private function setMailEvent(?string $receiver = null): void
    {
        if (!$receiver || empty($receiver)) {
            return;
        }

        $event = new CmsFormEvent(
            $this->getContext(),
            $this->getSalesChannelContext()->getSalesChannel()->getId(),
            new MailRecipientStruct([$receiver => $receiver]),
            $this->getCurrentForm(),
            $this->form->getMedias()
        );

        $this->eventDispatcher->dispatch(
            $event,
            MoorlFormBuilder::MAIL_TEMPLATE_MAIL_SEND_ACTION
        );
    }

    public function getCurrentForm(): ?FormEntity
    {
        return $this->form;
    }

    private function insertHistory(): void
    {
        $dataBag = $this->requestStack->getCurrentRequest();

        $data = [
            'id' => Uuid::randomHex(),
            'formId' => $this->form->getId(),
            'salesChannelId' => $this->getSalesChannelContext()->getSalesChannel()->getId(),
            'name' => $this->form->getName(),
            'email' => $this->form->getEmailReceiver(),
            'requestData' => $dataBag->request->all(),
            'data' => $this->form->getUserValues(),
            'media' => $this->form->getMedias() ? $this->form->getMedias()->getIds() : null
        ];

        $repository = $this->definitionInstanceRegistry->getRepository('moorl_form_history');

        $repository->create([$data], $this->getContext());
    }

    private function insertDatabase(): void
    {
        $entityName = $this->form->getRelatedEntity();
        if (!$entityName) {
            return;
        }

        if ($entityName === 'customer' && $this->salesChannelContext && $this->salesChannelContext->getCustomer()) {
            $data = ['id' => $this->salesChannelContext->getCustomer()->getId()];
        } else {
            $data = ['id' => Uuid::randomHex()];
        }

        $userValues = $this->form->getUserValues();

        foreach ($this->form->getData() as $formElement) {
            if (in_array($formElement['type'], ['checkbox','switch'])) {
                if (isset($userValues[$formElement['name']])) {
                    $userValues[$formElement['name']] = 'true';
                } else {
                    $userValues[$formElement['name']] = 'false';
                }
            }

            if ($formElement['name'] && $formElement['mapping'] && isset($userValues[$formElement['name']])) {
                if (!empty($userValues[$formElement['name']])) {
                    if ($userValues[$formElement['name']] === 'true') {
                        $data[$formElement['mapping']] = true;
                    } elseif ($userValues[$formElement['name']] === 'false') {
                        $data[$formElement['mapping']] = false;
                    } else {
                        $data[$formElement['mapping']] = $userValues[$formElement['name']];
                    }
                }
            }
        }

        PluginHelpers::getNestedVar($data);

        $repository = $this->definitionInstanceRegistry->getRepository($entityName);

        $repository->upsert([$data], $this->getContext());
    }

    public function extendFileTypeWhitelist(?array $whitelist): array
    {
        if (!$whitelist) {
            $whitelist = $this->whitelist;
        }

        $whitelistConfig = $this->systemConfigService->get('MoorlFormBuilder.config.fileExtensions');

        if ($whitelistConfig) {
            $whitelistConfig = explode(",", $whitelistConfig);
            $whitelistConfig = array_map('trim', $whitelistConfig);
            if (is_array($whitelistConfig)) {
                $whitelist = array_merge($whitelist, $whitelistConfig);
            }
        }

        return $whitelist;
    }

    public function sanitizeFormData()
    {
        // TODO: remove unused data to minify payloads etc
    }

    public function addFlashBag(array $violations): void
    {
        $this->initSession();

        if ($violations) {
            foreach ($violations as $violation) {
                $this->session->getFlashBag()->add('danger', $violation['snippet']);
                /* TODO: translate this
                $this->trans($violation['snippet'], $violation['data']);
                */
            }
        }
    }
}
