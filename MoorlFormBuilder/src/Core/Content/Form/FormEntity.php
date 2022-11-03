<?php declare(strict_types=1);

namespace MoorlFormBuilder\Core\Content\Form;

use MoorlFormBuilder\Core\Content\FormElement\FormElement;
use MoorlFormBuilder\Core\Content\FormElement\FormElementInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Media\MediaCollection;
use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\Formatter\Crunched;
use ScssPhp\ScssPhp\Formatter\Expanded;

class FormEntity extends Entity
{
    use EntityIdTrait;
    use EntityCustomFieldsTrait;

    /**
     * @var FormElementInterface[]
     */
    protected iterable $formElements;
    protected ?string $salesChannelId = null;
    protected ?string $replyTo = null;
    protected ?string $summaryHTML = null;
    protected ?string $summaryPlain = null;
    protected ?array $payload = null;
    protected bool $initialized = false;
    protected ?array $submitText = null; // CHECK
    protected bool $submitDisabled = false; // CHECK
    protected ?array $successMessage = null;
    protected array $label;
    protected int $maxFileSize = 0;
    protected ?string $redirectTo = null;
    protected ?string $redirectParams = null;
    protected ?string $relatedEntity = null;
    protected bool $active = false;
    protected bool $insertNewsletter = false;
    protected bool $insertHistory = false;
    protected bool $privacy = true;
    protected bool $locked = false;
    protected bool $bootstrapGrid = true;
    protected bool $useCaptcha = false;
    protected bool $sendMail = false;
    protected ?string $captcha = null;
    protected ?string $stylesheet = null;
    /**
     * @depraced: v6.5
     */
    protected bool $useTrans = false;
    /**
     * @depraced: v6.5
     */
    protected bool $sendCopy = false;
    protected bool $bootstrapWideSpacing = false;
    protected bool $useSassCompiler = false;
    protected bool $insertDatabase = false;
    protected ?string $name = null;
    protected ?string $action = null;
    protected string $type;
    protected ?string $emailReceiver = null;
    protected ?string $customerEmailReceiver = null;
    protected ?string $mailTemplateId = null;
    protected ?string $customerMailTemplateId = null;
    protected ?string $mediaFolderId = null;
    protected ?string $mediaFolder = null;
    protected ?ProductCollection $products = null;
    protected ?MediaCollection $medias = null;
    protected ?array $data = null;
    protected ?array $userValues = [];
    protected array $binAttachments = [];
    protected ?array $redirectConditions = [];
    protected string $sendCopyType;

    /**
     * @return array|null
     */
    public function getSuccessMessage(): ?array
    {
        return $this->successMessage;
    }

    /**
     * @param array|null $successMessage
     */
    public function setSuccessMessage(?array $successMessage): void
    {
        $this->successMessage = $successMessage;
    }

    /**
     * @return FormElementInterface[]
     */
    public function getFormElements(): iterable
    {
        return $this->formElements;
    }

    /**
     * @param FormElementInterface[] $formElements
     */
    public function setFormElements(iterable $formElements): void
    {
        $this->formElements = $formElements;
    }

    /**
     * @return string|null
     */
    public function getSalesChannelId(): ?string
    {
        return $this->salesChannelId;
    }

    /**
     * @param string|null $salesChannelId
     */
    public function setSalesChannelId(?string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }

    /**
     * @return string|null
     */
    public function getReplyTo(): ?string
    {
        return $this->replyTo;
    }

    /**
     * @param string|null $replyTo
     */
    public function setReplyTo(?string $replyTo): void
    {
        $this->replyTo = $replyTo;
    }

    /**
     * @return string|null
     */
    public function getSummaryHTML(): ?string
    {
        return $this->summaryHTML;
    }

    /**
     * @param string|null $summaryHTML
     */
    public function setSummaryHTML(?string $summaryHTML): void
    {
        $this->summaryHTML = $summaryHTML;
    }

    /**
     * @return string|null
     */
    public function getSummaryPlain(): ?string
    {
        return $this->summaryPlain;
    }

    /**
     * @param string|null $summaryPlain
     */
    public function setSummaryPlain(?string $summaryPlain): void
    {
        $this->summaryPlain = $summaryPlain;
    }

    /**
     * @return array|null
     */
    public function getPayload(): ?array
    {
        return $this->payload;
    }

    /**
     * @param array|null $payload
     */
    public function setPayload(?array $payload): void
    {
        $this->payload = $payload;
    }

    /**
     * @return bool
     */
    public function getInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * @param bool $initialized
     */
    public function setInitialized(bool $initialized): void
    {
        $this->initialized = $initialized;
    }

    /**
     * @return array|null
     */
    public function getSubmitText(): ?array
    {
        return $this->submitText;
    }

    /**
     * @param array|null $submitText
     */
    public function setSubmitText(?array $submitText): void
    {
        $this->submitText = $submitText;
    }

    /**
     * @return bool
     */
    public function getSubmitDisabled(): bool
    {
        return $this->submitDisabled;
    }

    /**
     * @param bool $submitDisabled
     */
    public function setSubmitDisabled(bool $submitDisabled): void
    {
        $this->submitDisabled = $submitDisabled;
    }

    /**
     * @return array
     */
    public function getLabel(): array
    {
        return $this->label;
    }

    /**
     * @param array $label
     */
    public function setLabel(array $label): void
    {
        $this->label = $label;
    }

    /**
     * @return int
     */
    public function getMaxFileSize(): int
    {
        return $this->maxFileSize;
    }

    /**
     * @param int $maxFileSize
     */
    public function setMaxFileSize(int $maxFileSize): void
    {
        $this->maxFileSize = $maxFileSize;
    }

    /**
     * @return string|null
     */
    public function getRedirectTo(): ?string
    {
        return $this->redirectTo;
    }

    /**
     * @param string|null $redirectTo
     */
    public function setRedirectTo(?string $redirectTo): void
    {
        $this->redirectTo = $redirectTo;
    }

    /**
     * @return string|null
     */
    public function getRedirectParams(): ?string
    {
        return $this->redirectParams;
    }

    /**
     * @param string|null $redirectParams
     */
    public function setRedirectParams(?string $redirectParams): void
    {
        $this->redirectParams = $redirectParams;
    }

    /**
     * @return string|null
     */
    public function getRelatedEntity(): ?string
    {
        return $this->relatedEntity;
    }

    /**
     * @param string|null $relatedEntity
     */
    public function setRelatedEntity(?string $relatedEntity): void
    {
        $this->relatedEntity = $relatedEntity;
    }

    /**
     * @return bool
     */
    public function getActive(): bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     */
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    /**
     * @return bool
     */
    public function getInsertNewsletter(): bool
    {
        return $this->insertNewsletter;
    }

    /**
     * @param bool $insertNewsletter
     */
    public function setInsertNewsletter(bool $insertNewsletter): void
    {
        $this->insertNewsletter = $insertNewsletter;
    }

    /**
     * @return bool
     */
    public function getInsertHistory(): bool
    {
        return $this->insertHistory;
    }

    /**
     * @param bool $insertHistory
     */
    public function setInsertHistory(bool $insertHistory): void
    {
        $this->insertHistory = $insertHistory;
    }

    /**
     * @return bool
     */
    public function getPrivacy(): bool
    {
        return $this->privacy;
    }

    /**
     * @param bool $privacy
     */
    public function setPrivacy(bool $privacy): void
    {
        $this->privacy = $privacy;
    }

    /**
     * @return bool
     */
    public function getLocked(): bool
    {
        return $this->locked;
    }

    /**
     * @param bool $locked
     */
    public function setLocked(bool $locked): void
    {
        $this->locked = $locked;
    }

    /**
     * @return bool
     */
    public function getBootstrapGrid(): bool
    {
        return $this->bootstrapGrid;
    }

    /**
     * @param bool $bootstrapGrid
     */
    public function setBootstrapGrid(bool $bootstrapGrid): void
    {
        $this->bootstrapGrid = $bootstrapGrid;
    }

    /**
     * @return bool
     */
    public function getUseCaptcha(): bool
    {
        return $this->useCaptcha;
    }

    /**
     * @param bool $useCaptcha
     */
    public function setUseCaptcha(bool $useCaptcha): void
    {
        $this->useCaptcha = $useCaptcha;
    }

    /**
     * @return bool
     */
    public function getSendMail(): bool
    {
        return $this->sendMail;
    }

    /**
     * @param bool $sendMail
     */
    public function setSendMail(bool $sendMail): void
    {
        $this->sendMail = $sendMail;
    }

    /**
     * @return string|null
     */
    public function getCaptcha(): ?string
    {
        return $this->captcha;
    }

    /**
     * @param string|null $captcha
     */
    public function setCaptcha(?string $captcha): void
    {
        $this->captcha = $captcha;
    }

    /**
     * @return bool
     */
    public function getUseTrans(): bool
    {
        return $this->useTrans;
    }

    /**
     * @param bool $useTrans
     */
    public function setUseTrans(bool $useTrans): void
    {
        $this->useTrans = $useTrans;
    }

    /**
     * @return bool
     */
    public function getSendCopy(): bool
    {
        return $this->sendCopy;
    }

    /**
     * @param bool $sendCopy
     */
    public function setSendCopy(bool $sendCopy): void
    {
        $this->sendCopy = $sendCopy;
    }

    /**
     * @return bool
     */
    public function getBootstrapWideSpacing(): bool
    {
        return $this->bootstrapWideSpacing;
    }

    /**
     * @param bool $bootstrapWideSpacing
     */
    public function setBootstrapWideSpacing(bool $bootstrapWideSpacing): void
    {
        $this->bootstrapWideSpacing = $bootstrapWideSpacing;
    }

    /**
     * @return bool
     */
    public function getUseSassCompiler(): bool
    {
        return $this->useSassCompiler;
    }

    /**
     * @param bool $useSassCompiler
     */
    public function setUseSassCompiler(bool $useSassCompiler): void
    {
        $this->useSassCompiler = $useSassCompiler;
    }

    /**
     * @return bool
     */
    public function getInsertDatabase(): bool
    {
        return $this->insertDatabase;
    }

    /**
     * @param bool $insertDatabase
     */
    public function setInsertDatabase(bool $insertDatabase): void
    {
        $this->insertDatabase = $insertDatabase;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getAction(): ?string
    {
        return $this->action;
    }

    /**
     * @param string|null $action
     */
    public function setAction(?string $action): void
    {
        $this->action = $action;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string|null
     */
    public function getEmailReceiver(): ?string
    {
        return $this->emailReceiver;
    }

    /**
     * @param string|null $emailReceiver
     */
    public function setEmailReceiver(?string $emailReceiver): void
    {
        $this->emailReceiver = $emailReceiver;
    }

    /**
     * @return string|null
     */
    public function getCustomerEmailReceiver(): ?string
    {
        return $this->customerEmailReceiver;
    }

    /**
     * @param string|null $customerEmailReceiver
     */
    public function setCustomerEmailReceiver(?string $customerEmailReceiver): void
    {
        $this->customerEmailReceiver = $customerEmailReceiver;
    }

    /**
     * @return string|null
     */
    public function getMailTemplateId(): ?string
    {
        return $this->mailTemplateId;
    }

    /**
     * @param string|null $mailTemplateId
     */
    public function setMailTemplateId(?string $mailTemplateId): void
    {
        $this->mailTemplateId = $mailTemplateId;
    }

    /**
     * @return string|null
     */
    public function getCustomerMailTemplateId(): ?string
    {
        return $this->customerMailTemplateId;
    }

    /**
     * @param string|null $customerMailTemplateId
     */
    public function setCustomerMailTemplateId(?string $customerMailTemplateId): void
    {
        $this->customerMailTemplateId = $customerMailTemplateId;
    }

    /**
     * @return string|null
     */
    public function getMediaFolderId(): ?string
    {
        return $this->mediaFolderId;
    }

    /**
     * @param string|null $mediaFolderId
     */
    public function setMediaFolderId(?string $mediaFolderId): void
    {
        $this->mediaFolderId = $mediaFolderId;
    }

    /**
     * @return string|null
     */
    public function getMediaFolder(): ?string
    {
        return $this->mediaFolder;
    }

    /**
     * @param string|null $mediaFolder
     */
    public function setMediaFolder(?string $mediaFolder): void
    {
        $this->mediaFolder = $mediaFolder;
    }

    /**
     * @return ProductCollection|null
     */
    public function getProducts(): ?ProductCollection
    {
        return $this->products;
    }

    /**
     * @param ProductCollection|null $products
     */
    public function setProducts(?ProductCollection $products): void
    {
        $this->products = $products;
    }

    /**
     * @return MediaCollection|null
     */
    public function getMedias(): ?MediaCollection
    {
        return $this->medias;
    }

    /**
     * @param MediaCollection|null $medias
     */
    public function setMedias(?MediaCollection $medias): void
    {
        $this->medias = $medias;
    }

    /**
     * @return array|null
     */
    public function getUserValues(): ?array
    {
        return $this->userValues;
    }

    /**
     * @param array|null $userValues
     */
    public function setUserValues(?array $userValues): void
    {
        $this->userValues = $userValues;
    }

    /**
     * @return array
     */
    public function getBinAttachments(): array
    {
        return $this->binAttachments;
    }

    /**
     * @param array $binAttachments
     */
    public function setBinAttachments(array $binAttachments): void
    {
        $this->binAttachments = $binAttachments;
    }

    /**
     * @return array|null
     */
    public function getRedirectConditions(): ?array
    {
        return $this->redirectConditions;
    }

    /**
     * @param array|null $redirectConditions
     */
    public function setRedirectConditions(?array $redirectConditions): void
    {
        $this->redirectConditions = $redirectConditions;
    }

    /**
     * @return string
     */
    public function getSendCopyType(): string
    {
        return $this->sendCopyType;
    }

    /**
     * @param string $sendCopyType
     */
    public function setSendCopyType(string $sendCopyType): void
    {
        $this->sendCopyType = $sendCopyType;
    }

    /* Custom methods below */
    public function addBinAttachment(array $binAttachment): void
    {
        $this->binAttachments[] = $binAttachment;
    }

    public function getBootstrapRow(): string
    {
        if ($this->bootstrapWideSpacing) {
            return "row";
        }
        return "row form-row";
    }

    public function getRepeaterDataValue(string $technicalName): ?string
    {
        if ($this->data && is_array($this->data)) {
            foreach ($this->data as $formElement) {
                if ($formElement['name'] === $technicalName && is_array($formElement['value'])) {
                    $i = 0;
                    $html = "";
                    foreach ($formElement['value'] as $repeater) {
                        $i++;
                        if (is_array($repeater)) {
                            $html .= "\n#" . $i . "\n";
                            foreach ($repeater as $repeaterKey => $repeaterValue) {
                                $html .= $this->getDataLabel($repeaterKey) . ": ";
                                $html .= $this->getDataValue($repeaterKey, $repeaterValue);
                                $html .= "\n";
                            }
                        } else {
                            $html .= "\n#" . $i . "\n";
                            $html .= $repeater;
                        }
                    }
                    return $html;
                }
            }
        }

        return null;
    }

    public function getDataLabel(string $technicalName): ?string
    {
        if ($this->data && is_array($this->data)) {
            foreach ($this->data as $formElement) {
                if ($formElement['name'] === $technicalName) {
                    return $formElement['translated']['label'];
                }
            }
        }

        return null;
    }

    public function getDataValue(string $technicalName, $value = null): ?string
    {
        if ($this->data && is_array($this->data)) {
            foreach ($this->data as $formElement) {
                if ($formElement['name'] === $technicalName) {
                    $value = $value ?: $formElement['value'];
                    if (in_array($formElement['type'], ['select','multiselect','radio-group','checkbox-group'])) {
                        $txt = [];
                        foreach ($formElement['options'] as $option) {
                            if ($option['value'] == $value || (is_array($value) && in_array($option['value'], $value))) {
                                $txt[] = $option['translated']['label'];
                            }
                        }
                        return implode(', ', $txt);
                    } else {
                        return $value;
                    }
                }
            }
        }

        return null;
    }

    public function getFormElement(string $technicalName): FormElementInterface
    {
        foreach ($this->formElements as $formElement) {
            if ($formElement->getName() === $technicalName) {
                return $formElement;
            }
        }
    }

    public function getStylesheet(): ?string
    {
        if ($this->useSassCompiler && $this->stylesheet) {
            $compiler = new Compiler();
            $compiler->setFormatter(Expanded::class);
            return $compiler->compile(sprintf("#form-%s { %s }", $this->id, $this->stylesheet));
        }

        return $this->stylesheet;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(?array $data): void
    {
        $this->data = $data;

        $this->formElements = [];
        foreach ($data as $formElement) {
            $this->formElements[] = new FormElement($formElement);
        }
    }

    public function addData(string $name, array $value): void
    {
        $this->data[$name] = $value;

        $this->formElements[] = new FormElement($value);
    }

    public function enrichUserValues(?array $userValues = null): void
    {
        if (!$userValues) {
            return;
        }

        foreach ($this->data as &$dataValue) {
            if (isset($userValues[$dataValue['name']])) {
                $dataValue['value'] = $userValues[$dataValue['name']];
            }
        }

        foreach ($this->formElements as $formElement) {
            if (isset($userValues[$formElement->getName()])) {
                $formElement->setValue($userValues[$formElement->getName()]);
            }
        }
    }
}
