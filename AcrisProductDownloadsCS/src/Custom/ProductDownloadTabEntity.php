<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Custom;

use Acris\ProductDownloads\Custom\Aggregate\ProductDownloadTabTranslation\ProductDownloadTabTranslationCollection;
use Shopware\Core\Content\Rule\RuleCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class ProductDownloadTabEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $internalId;

    /**
     * @var int|null
     */
    protected $priority;

    /**
     * @var ProductDownloadCollection|null
     */
    protected $acrisDownloads;

    /**
     * @var ProductDownloadTabTranslationCollection|null
     */
    protected $translations;

    /**
     * @var RuleCollection|null
     */
    protected $rules;

    /**
     * @return string
     */
    public function getInternalId(): string
    {
        return $this->internalId;
    }

    /**
     * @param string $internalId
     */
    public function setInternalId(string $internalId): void
    {
        $this->internalId = $internalId;
    }

    /**
     * @return int|null
     */
    public function getPriority(): ?int
    {
        return $this->priority;
    }

    /**
     * @param int|null $priority
     */
    public function setPriority(?int $priority): void
    {
        $this->priority = $priority;
    }

    /**
     * @return ProductDownloadCollection|null
     */
    public function getAcrisDownloads(): ?ProductDownloadCollection
    {
        return $this->acrisDownloads;
    }

    /**
     * @param ProductDownloadCollection|null $acrisDownloads
     */
    public function setAcrisDownloads(?ProductDownloadCollection $acrisDownloads): void
    {
        $this->acrisDownloads = $acrisDownloads;
    }

    /**
     * @return ProductDownloadTabTranslationCollection|null
     */
    public function getTranslations(): ?ProductDownloadTabTranslationCollection
    {
        return $this->translations;
    }

    /**
     * @param ProductDownloadTabTranslationCollection|null $translations
     */
    public function setTranslations(?ProductDownloadTabTranslationCollection $translations): void
    {
        $this->translations = $translations;
    }

    /**
     * @return RuleCollection|null
     */
    public function getRules(): ?RuleCollection
    {
        return $this->rules;
    }

    /**
     * @param RuleCollection|null $rules
     */
    public function setRules(?RuleCollection $rules): void
    {
        $this->rules = $rules;
    }
}
