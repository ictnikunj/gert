<?php declare(strict_types=1);

namespace MoorlFormBuilder\Core\Content\FormAppointment;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class FormAppointmentEntity extends Entity
{
    use EntityIdTrait;

    protected ?string $formId = null;
    protected string $formElement;
    protected ?string $salesChannelId = null;
    protected ?string $productId = null;
    protected ?string $orderId = null;
    protected ?string $customerId = null;
    protected bool $active = false;
    protected \DateTimeInterface $start;
    protected ?\DateTimeInterface $end = null;

    /**
     * @return string|null
     */
    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    /**
     * @param string|null $customerId
     */
    public function setCustomerId(?string $customerId): void
    {
        $this->customerId = $customerId;
    }

    /**
     * @return string|null
     */
    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    /**
     * @param string|null $orderId
     */
    public function setOrderId(?string $orderId): void
    {
        $this->orderId = $orderId;
    }

    /**
     * @return string|null
     */
    public function getFormId(): ?string
    {
        return $this->formId;
    }

    /**
     * @param string|null $formId
     */
    public function setFormId(?string $formId): void
    {
        $this->formId = $formId;
    }

    /**
     * @return string
     */
    public function getFormElement(): string
    {
        return $this->formElement;
    }

    /**
     * @param string $formElement
     */
    public function setFormElement(string $formElement): void
    {
        $this->formElement = $formElement;
    }

    /**
     * @return string|null
     */
    public function getSalesChannelId(): ?string
    {
        return $this->salesChannelId;
    }

    /**
     * @param string|null $salesChannelId
     */
    public function setSalesChannelId(?string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }

    /**
     * @return string|null
     */
    public function getProductId(): ?string
    {
        return $this->productId;
    }

    /**
     * @param string|null $productId
     */
    public function setProductId(?string $productId): void
    {
        $this->productId = $productId;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     */
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getStart(): \DateTimeInterface
    {
        return $this->start;
    }

    /**
     * @param \DateTimeInterface $start
     */
    public function setStart(\DateTimeInterface $start): void
    {
        $this->start = $start;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getEnd(): ?\DateTimeInterface
    {
        return $this->end;
    }

    /**
     * @param \DateTimeInterface|null $end
     */
    public function setEnd(?\DateTimeInterface $end): void
    {
        $this->end = $end;
    }
}
