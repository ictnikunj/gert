<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form;

use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\MailTemplate\MailTemplateEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Swag\CmsExtensions\Form\Aggregate\FormGroup\FormGroupCollection;
use Swag\CmsExtensions\Form\Aggregate\FormTranslation\FormTranslationCollection;

class FormEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string|null
     */
    protected $cmsSlotId;

    /**
     * @var bool
     */
    protected $isTemplate;

    /**
     * @var string
     */
    protected $technicalName;

    /**
     * @var string|null
     */
    protected $title;

    /**
     * @var string|null
     */
    protected $successMessage;

    /**
     * @var array|null
     */
    protected $receivers;

    /**
     * @var string
     */
    protected $mailTemplateId;

    /**
     * @var FormGroupCollection|null
     */
    protected $groups;

    /**
     * @var CmsSlotEntity|null
     */
    protected $cmsSlot;

    /**
     * @var MailTemplateEntity|null
     */
    protected $mailTemplate;

    /**
     * @var FormTranslationCollection
     */
    protected $translations;

    public function getCmsSlotId(): ?string
    {
        return $this->cmsSlotId;
    }

    public function setCmsSlotId(?string $cmsSlotId): void
    {
        $this->cmsSlotId = $cmsSlotId;
    }

    /**
     * @deprecated tag:v3.0.0, since 2.1.0 use getIsTemplate() instead
     */
    public function isTemplate(): bool
    {
        return $this->getIsTemplate();
    }

    public function getIsTemplate(): bool
    {
        return $this->isTemplate;
    }

    public function setIsTemplate(bool $isTemplate): void
    {
        $this->isTemplate = $isTemplate;
    }

    public function getTechnicalName(): string
    {
        return $this->technicalName;
    }

    public function setTechnicalName(string $technicalName): void
    {
        $this->technicalName = $technicalName;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getSuccessMessage(): ?string
    {
        return $this->successMessage;
    }

    public function setSuccessMessage(?string $successMessage): void
    {
        $this->successMessage = $successMessage;
    }

    public function getReceivers(): ?array
    {
        return $this->receivers;
    }

    public function setReceivers(?array $receivers): void
    {
        $this->receivers = $receivers;
    }

    public function getMailTemplateId(): string
    {
        return $this->mailTemplateId;
    }

    public function setMailTemplateId(string $mailTemplateId): void
    {
        $this->mailTemplateId = $mailTemplateId;
    }

    public function getGroups(): ?FormGroupCollection
    {
        return $this->groups;
    }

    public function setGroups(FormGroupCollection $groups): void
    {
        $this->groups = $groups;
    }

    public function getCmsSlot(): ?CmsSlotEntity
    {
        return $this->cmsSlot;
    }

    public function setCmsSlot(?CmsSlotEntity $cmsSlot): void
    {
        $this->cmsSlot = $cmsSlot;
    }

    public function getMailTemplate(): ?MailTemplateEntity
    {
        return $this->mailTemplate;
    }

    public function setMailTemplate(?MailTemplateEntity $mailTemplate): void
    {
        $this->mailTemplate = $mailTemplate;
    }

    public function getTranslations(): FormTranslationCollection
    {
        return $this->translations;
    }

    public function setTranslations(FormTranslationCollection $translations): void
    {
        $this->translations = $translations;
    }
}
