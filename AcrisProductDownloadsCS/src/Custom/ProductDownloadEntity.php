<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Custom;

use Acris\ProductDownloads\Custom\Aggregate\ProductDownloadTranslation\ProductDownloadTranslationCollection;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\Language\LanguageCollection;

class ProductDownloadEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string|null
     */
    protected $downloadTabId;

    /**
     * @var ProductDownloadTabEntity|null
     */
    protected $downloadTab;
    /**
     * @var LanguageCollection|null
     */
    protected $languages;

    /**
     * @var string
     */
    protected $productId;

    /**
     * @var ProductEntity
     */
    protected $product;

    /**
     * @var int|null
     */
    protected $position;

    /**
     * @var string
     */
    protected $mediaId;

    /**
     * @var string|null
     */
    protected $previewMediaId;

    /**
     * @var array|null
     */
    protected $languageIds;

    /**
     * @var MediaEntity
     */
    protected $media;

    /**
     * @var MediaEntity|null
     */
    protected $previewMedia;

    /**
     * @var ProductDownloadTranslationCollection|null
     */
    protected $translations;

    /**
     * @var boolean
     */
    protected $previewImageEnabled;

    /**
     * @return LanguageCollection|null
     */
    public function getLanguages(): ?LanguageCollection
    {
        return $this->languages;
    }

    /**
     * @param LanguageCollection|null $languages
     */
    public function setLanguages(?LanguageCollection $languages): void
    {
        $this->languages = $languages;
    }

    /**
     * @return string
     */
    public function getMediaId(): string
    {
        return $this->mediaId;
    }

    /**
     * @param string $mediaId
     */
    public function setMediaId(string $mediaId): void
    {
        $this->mediaId = $mediaId;
    }

    /**
     * @return MediaEntity
     */
    public function getMedia(): MediaEntity
    {
        return $this->media;
    }

    /**
     * @param MediaEntity $media
     */
    public function setMedia(MediaEntity $media): void
    {
        $this->media = $media;
    }

    /**
     * @return ProductEntity
     */
    public function getProduct(): ProductEntity
    {
        return $this->product;
    }

    /**
     * @param ProductEntity $product
     */
    public function setProduct(ProductEntity $product): void
    {
        $this->product = $product;
    }

    /**
     * @return string
     */
    public function getProductId(): string
    {
        return $this->productId;
    }

    /**
     * @param string $productId
     */
    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }

    /**
     * @return int|null
     */
    public function getPosition(): ?int
    {
        return $this->position;
    }

    /**
     * @param int|null $position
     */
    public function setPosition(?int $position): void
    {
        $this->position = $position;
    }

    /**
     * @return ProductDownloadTranslationCollection|null
     */
    public function getTranslations(): ?ProductDownloadTranslationCollection
    {
        return $this->translations;
    }

    /**
     * @param ProductDownloadTranslationCollection|null $translations
     */
    public function setTranslations(?ProductDownloadTranslationCollection $translations): void
    {
        $this->translations = $translations;
    }

    /**
     * @return array|null
     */
    public function getLanguageIds(): ?array
    {
        return $this->languageIds;
    }

    /**
     * @param array|null $languageIds
     */
    public function setLanguageIds(?array $languageIds): void
    {
        $this->languageIds = $languageIds;
    }

    /**
     * @return bool
     */
    public function isPreviewImageEnabled(): bool
    {
        return $this->previewImageEnabled;
    }

    /**
     * @param bool $previewImageEnabled
     */
    public function setPreviewImageEnabled(bool $previewImageEnabled): void
    {
        $this->previewImageEnabled = $previewImageEnabled;
    }

    /**
     * @return string|null
     */
    public function getPreviewMediaId(): ?string
    {
        return $this->previewMediaId;
    }

    /**
     * @param string|null $previewMediaId
     */
    public function setPreviewMediaId(?string $previewMediaId): void
    {
        $this->previewMediaId = $previewMediaId;
    }

    /**
     * @return MediaEntity|null
     */
    public function getPreviewMedia(): ?MediaEntity
    {
        return $this->previewMedia;
    }

    /**
     * @param MediaEntity|null $previewMedia
     */
    public function setPreviewMedia(?MediaEntity $previewMedia): void
    {
        $this->previewMedia = $previewMedia;
    }

    /**
     * @return string|null
     */
    public function getDownloadTabId(): ?string
    {
        return $this->downloadTabId;
    }

    /**
     * @param string|null $downloadTabId
     */
    public function setDownloadTabId(?string $downloadTabId): void
    {
        $this->downloadTabId = $downloadTabId;
    }

    /**
     * @return ProductDownloadTabEntity|null
     */
    public function getDownloadTab(): ?ProductDownloadTabEntity
    {
        return $this->downloadTab;
    }

    /**
     * @param ProductDownloadTabEntity|null $downloadTab
     */
    public function setDownloadTab(?ProductDownloadTabEntity $downloadTab): void
    {
        $this->downloadTab = $downloadTab;
    }
}
