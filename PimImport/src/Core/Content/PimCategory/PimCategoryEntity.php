<?php declare(strict_types=1);

namespace PimImport\Core\Content\PimCategory;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class PimCategoryEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $id;

    /**
     * @var string|null
     */
    protected $categoryId;

    /**
     * @var string|null
     */
    protected $categoryCode;

    /**
     * @var string|null
     */
    protected $salesChannelId;

    /**
     * @var \DateTimeInterface|null
     */
    protected $lastUsageAt;

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

    public function getCategoryId(): ?string
    {
        return $this->categoryId;
    }

    public function setCategoryId(?string $categoryId): void
    {
        $this->categoryId = $categoryId;
    }

    public function getCategoryCode(): ?string
    {
        return $this->categoryCode;
    }

    public function setCategoryCode(?string $categoryCode): void
    {
        $this->categoryCode = $categoryCode;
    }

    public function getSalesChannelId(): ?string
    {
        return $this->salesChannelId;
    }

    public function setSalesChannelId(?string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }

    public function getLastUsageAt(): ?\DateTimeInterface
    {
        return $this->lastUsageAt;
    }

    public function setLastUsageAt(?\DateTimeInterface $lastUsageAt): void
    {
        $this->lastUsageAt = $lastUsageAt;
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
