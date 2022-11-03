<?php declare(strict_types=1);

namespace MoorlFormBuilder\Core\Content\FormHistory;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Media\MediaCollection;

class FormHistoryEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $id;
    /**
     * @var array|null
     */
    protected $successMessage;
    /**
     * @var array|null
     */
    protected $label;
    /**
     * @var array|null
     */
    protected $customFields;
    /**
     * @var int|null
     */
    protected $maxFileSize;
    /**
     * @var string|null
     */
    protected $redirectTo;
    /**
     * @var string|null
     */
    protected $relatedEntity;
    /**
     * @var bool|null
     */
    protected $active;
    /**
     * @var bool|null
     */
    protected $insertNewsletter;
    /**
     * @var bool|null
     */
    protected $insertHistory;
    /**
     * @var bool|null
     */
    protected $privacy;
    /**
     * @var bool|null
     */
    protected $locked;
    /**
     * @var bool|null
     */
    protected $bootstrapGrid;
    /**
     * @var bool|null
     */
    protected $useCaptcha;
    /**
     * @var bool|null
     */
    protected $sendMail;
    /**
     * @var string|null
     */
    protected $captcha;
    /**
     * @var string|null
     */
    protected $stylesheet;
    /**
     * @var bool|null
     */
    protected $useTrans;
    /**
     * @var bool|null
     */
    protected $sendCopy;
    /**
     * @var bool|null
     */
    protected $insertDatabase;
    /**
     * @var string|null
     */
    protected $name;
    /**
     * @var string|null
     */
    protected $action;
    /**
     * @var string|null
     */
    protected $type;
    /**
     * @var string|null
     */
    protected $emailReceiver;
    /**
     * @var string|null
     */
    protected $mailTemplateId;
    /**
     * @var string|null
     */
    protected $mediaFolderId;
    /**
     * @var string|null
     */
    protected $mediaFolder;
    /**
     * @var ProductCollection|null
     */
    protected $products;
    /**
     * @var MediaCollection|null
     */
    protected $medias;
    /**
     * @var array|null
     */
    protected $data;
    /**
     * @var array|null
     */
    protected $userValues;

    /**
     * @return bool|null
     */
    public function getInsertHistory(): ?bool
    {
        return $this->insertHistory;
    }

    /**
     * @param bool|null $insertHistory
     */
    public function setInsertHistory(?bool $insertHistory): void
    {
        $this->insertHistory = $insertHistory;
    }

    /**
     * @return bool|null
     */
    public function getInsertNewsletter(): ?bool
    {
        return $this->insertNewsletter;
    }

    /**
     * @param bool|null $insertNewsletter
     */
    public function setInsertNewsletter(?bool $insertNewsletter): void
    {
        $this->insertNewsletter = $insertNewsletter;
    }

    /**
     * @return array|null
     */
    public function getLabel(): ?array
    {
        return $this->label;
    }

    /**
     * @param array|null $label
     */
    public function setLabel(?array $label): void
    {
        $this->label = $label;
    }

    /**
     * @return array|null
     */
    public function getCustomFields(): ?array
    {
        return $this->customFields;
    }

    /**
     * @param array|null $customFields
     */
    public function setCustomFields(?array $customFields): void
    {
        $this->customFields = $customFields;
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
     * @return string|null
     */
    public function getStylesheet(): ?string
    {
        return $this->stylesheet;
    }

    /**
     * @param string|null $stylesheet
     */
    public function setStylesheet(?string $stylesheet): void
    {
        $this->stylesheet = $stylesheet;
    }

    /**
     * @return bool|null
     */
    public function getSendMail(): ?bool
    {
        return $this->sendMail;
    }

    /**
     * @param bool|null $sendMail
     */
    public function setSendMail(?bool $sendMail): void
    {
        $this->sendMail = $sendMail;
    }

    /**
     * @return int|null
     */
    public function getMaxFileSize(): ?int
    {
        return $this->maxFileSize;
    }

    /**
     * @param int|null $maxFileSize
     */
    public function setMaxFileSize(?int $maxFileSize): void
    {
        $this->maxFileSize = $maxFileSize;
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
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

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
     * @return bool|null
     */
    public function getActive(): ?bool
    {
        return $this->active;
    }

    /**
     * @param bool|null $active
     */
    public function setActive(?bool $active): void
    {
        $this->active = $active;
    }

    /**
     * @return bool|null
     */
    public function getPrivacy(): ?bool
    {
        return $this->privacy;
    }

    /**
     * @param bool|null $privacy
     */
    public function setPrivacy(?bool $privacy): void
    {
        $this->privacy = $privacy;
    }

    /**
     * @return bool|null
     */
    public function getLocked(): ?bool
    {
        return $this->locked;
    }

    /**
     * @param bool|null $locked
     */
    public function setLocked(?bool $locked): void
    {
        $this->locked = $locked;
    }

    /**
     * @return bool|null
     */
    public function getBootstrapGrid(): ?bool
    {
        return $this->bootstrapGrid;
    }

    /**
     * @param bool|null $bootstrapGrid
     */
    public function setBootstrapGrid(?bool $bootstrapGrid): void
    {
        $this->bootstrapGrid = $bootstrapGrid;
    }

    /**
     * @return bool|null
     */
    public function getUseCaptcha(): ?bool
    {
        return $this->useCaptcha;
    }

    /**
     * @param bool|null $useCaptcha
     */
    public function setUseCaptcha(?bool $useCaptcha): void
    {
        $this->useCaptcha = $useCaptcha;
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
     * @return bool|null
     */
    public function getUseTrans(): ?bool
    {
        return $this->useTrans;
    }

    /**
     * @param bool|null $useTrans
     */
    public function setUseTrans(?bool $useTrans): void
    {
        $this->useTrans = $useTrans;
    }

    /**
     * @return bool|null
     */
    public function getSendCopy(): ?bool
    {
        return $this->sendCopy;
    }

    /**
     * @param bool|null $sendCopy
     */
    public function setSendCopy(?bool $sendCopy): void
    {
        $this->sendCopy = $sendCopy;
    }

    /**
     * @return bool|null
     */
    public function getInsertDatabase(): ?bool
    {
        return $this->insertDatabase;
    }

    /**
     * @param bool|null $insertDatabase
     */
    public function setInsertDatabase(?bool $insertDatabase): void
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
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string|null $type
     */
    public function setType(?string $type): void
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
     * @return array|null
     */
    public function getData(): ?array
    {
        return $this->data;
    }

    /**
     * @param array|null $data
     */
    public function setData(?array $data): void
    {
        $this->data = $data;
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



}
