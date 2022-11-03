<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Custom\Aggregate\ProductLinkTranslation;

use Acris\ProductDownloads\Custom\ProductLinkEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Shopware\Core\Framework\DataAbstractionLayer\TranslationEntity;

class ProductLinkTranslationEntity extends TranslationEntity
{
    use EntityCustomFieldsTrait;

    /**
     * @var string
     */
    protected $acrisProductLinkId;

    /**
     * @var string|null
     */
    protected $title;

    /**
     * @var string|null
     */
    protected $description;

    /**
     * @var ProductLinkEntity
     */
    protected $acrisProductLink;

    /**
     * @return string
     */
    public function getAcrisProductLinkId(): string
    {
        return $this->acrisProductLinkId;
    }

    /**
     * @param string $acrisProductLinkId
     */
    public function setAcrisProductLinkId(string $acrisProductLinkId): void
    {
        $this->acrisProductLinkId = $acrisProductLinkId;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     */
    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return ProductLinkEntity
     */
    public function getAcrisProductLink(): ProductLinkEntity
    {
        return $this->acrisProductLink;
    }

    /**
     * @param ProductLinkEntity $acrisProductLink
     */
    public function setAcrisProductLink(ProductLinkEntity $acrisProductLink): void
    {
        $this->acrisProductLink = $acrisProductLink;
    }
}
