<?php declare(strict_types=1);

namespace PimImport\Core\Content\Pim;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class PimProductEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $id;

    /**
     * @var string|null
     */
    protected $productNumber;

    /**
     * @var \DateTimeInterface|null
     */
    protected $lastUsageAt;

    /**
     * @var \DateTimeInterface|null
     */
    protected $lastRelatedCrossSellUsage;

    /**
     * @var \DateTimeInterface|null
     */
    protected $updatedAt;

    /**
     * @var \DateTimeInterface
     */
    protected $createdAt;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getProductNumber(): ?string
    {
        return $this->productNumber;
    }

    public function setProductNumber(?string $productNumber): void
    {
        $this->productNumber = $productNumber;
    }

    public function getLastUsageAt(): ?\DateTimeInterface
    {
        return $this->lastUsageAt;
    }

    public function setLastUsageAt(?\DateTimeInterface $lastUsageAt): void
    {
        $this->lastUsageAt = $lastUsageAt;
    }

    public function getlastRelatedCrossSellUsage(): ?\DateTimeInterface
    {
        return $this->lastRelatedCrossSellUsage;
    }

    public function setlastRelatedCrossSellUsage(?\DateTimeInterface $lastRelatedCrossSellUsage): void
    {
        $this->lastRelatedCrossSellUsage = $lastRelatedCrossSellUsage;
    }

    public function getlastProductPartCrossSellUsage(): ?\DateTimeInterface
    {
        return $this->lastProductPartCrossSellUsage;
    }

    public function setlastProductPartCrossSellUsage(?\DateTimeInterface $lastProductPartCrossSellUsage): void
    {
        $this->lastProductPartCrossSellUsage = $lastProductPartCrossSellUsage;
    }

    public function getlastAddonCrossSellUsage(): ?\DateTimeInterface
    {
        return $this->lastAddonCrossSellUsage;
    }

    public function setlastAddonCrossSellUsage(?\DateTimeInterface $lastAddonCrossSellUsage): void
    {
        $this->lastAddonCrossSellUsage = $lastAddonCrossSellUsage;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
