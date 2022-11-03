<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Custom\Aggregate\ProductDownloadTabTranslation;

use Acris\ProductDownloads\Custom\ProductDownloadTabEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Shopware\Core\Framework\DataAbstractionLayer\TranslationEntity;

class ProductDownloadTabTranslationEntity extends TranslationEntity
{
    use EntityCustomFieldsTrait;

    /**
     * @var string
     */
    protected $acrisDownloadTabId;

    /**
     * @var ProductDownloadTabEntity
     */
    protected $acrisDownloadTab;

    /**
     * @var string
     */
    protected $displayName;

    /**
     * @return string
     */
    public function getAcrisDownloadTabId(): string
    {
        return $this->acrisDownloadTabId;
    }

    /**
     * @param string $acrisDownloadTabId
     */
    public function setAcrisDownloadTabId(string $acrisDownloadTabId): void
    {
        $this->acrisDownloadTabId = $acrisDownloadTabId;
    }

    /**
     * @return ProductDownloadTabEntity
     */
    public function getAcrisDownloadTab(): ProductDownloadTabEntity
    {
        return $this->acrisDownloadTab;
    }

    /**
     * @param ProductDownloadTabEntity $acrisDownloadTab
     */
    public function setAcrisDownloadTab(ProductDownloadTabEntity $acrisDownloadTab): void
    {
        $this->acrisDownloadTab = $acrisDownloadTab;
    }

    /**
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    /**
     * @param string $displayName
     */
    public function setDisplayName(string $displayName): void
    {
        $this->displayName = $displayName;
    }
}
