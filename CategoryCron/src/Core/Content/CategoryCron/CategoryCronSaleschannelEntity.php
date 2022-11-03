<?php declare(strict_types=1);

namespace CategoryCron\Core\Content\CategoryCron;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
;

class CategoryCronSaleschannelEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $id;

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