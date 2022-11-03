<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Custom\Aggregate\ProductDownloadTranslation;

use Acris\ProductDownloads\Custom\ProductDownloadEntity;
use Shopware\Core\Framework\DataAbstractionLayer\TranslationEntity;

class ProductDownloadTranslationEntity extends TranslationEntity
{
    /**
     * @var string
     */
    protected $productDownloadId;

    /**
     * @var string|null
     */
    protected $title;

    /**
     * @var string|null
     */
    protected $description;

    /**
     * @var ProductDownloadEntity
     */
    protected $productDownload;

    /**
     * @return string
     */
    public function getProductDownloadId(): string
    {
        return $this->productDownloadId;
    }

    /**
     * @param string $productDownloadId
     */
    public function setProductDownloadId(string $productDownloadId): void
    {
        $this->productDownloadId = $productDownloadId;
    }

    /**
     * @return ProductDownloadEntity
     */
    public function getProductDownload(): ProductDownloadEntity
    {
        return $this->productDownload;
    }

    /**
     * @param ProductDownloadEntity $productDownload
     */
    public function setProductDownload(ProductDownloadEntity $productDownload): void
    {
        $this->productDownload = $productDownload;
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
}
