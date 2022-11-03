<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Custom;

use Acris\ProductDownloads\Custom\Aggregate\ProductLinkTranslation\ProductLinkTranslationCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\Language\LanguageCollection;

class ProductLinkEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var LanguageCollection|null
     */
    protected $languages;

    /**
     * @var string
     */
    protected $productId;

    /**
     * @var ProductEntity|null
     */
    protected $product;

    /**
     * @var int|null
     */
    protected $position;

    /**
     * @var array|null
     */
    protected $languageIds;

    /**
     * @var ProductLinkTranslationCollection|null
     */
    protected $translations;

    /**
     * @var string
     */
    protected $url;

    /**
     * @var bool|null
     */
    protected $linkTarget;

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
     * @return ProductEntity|null
     */
    public function getProduct(): ?ProductEntity
    {
        return $this->product;
    }

    /**
     * @param ProductEntity|null $product
     */
    public function setProduct(?ProductEntity $product): void
    {
        $this->product = $product;
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
     * @return ProductLinkTranslationCollection|null
     */
    public function getTranslations(): ?ProductLinkTranslationCollection
    {
        return $this->translations;
    }

    /**
     * @param ProductLinkTranslationCollection|null $translations
     */
    public function setTranslations(?ProductLinkTranslationCollection $translations): void
    {
        $this->translations = $translations;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * @return bool|null
     */
    public function getLinkTarget(): ?bool
    {
        return $this->linkTarget;
    }

    /**
     * @param bool|null $linkTarget
     */
    public function setLinkTarget(?bool $linkTarget): void
    {
        $this->linkTarget = $linkTarget;
    }
}
