<?php declare(strict_types=1);

namespace Ropi\FrontendEditing\Core\Content\ContentDocument;

use Ropi\ContentEditor\ContentDocument\ContentDocumentInterface;
use Ropi\ContentEditor\ContentDocument\ContentDocumentTrait;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;

class ContentDocumentEntity extends Entity implements ContentDocumentInterface
{
    use ContentDocumentTrait;

    /**
     * @var string|null
     */
    protected $languageId;

    /**
     * @var string|null
     */
    protected $salesChannelId;

    /**
     * @var string|null
     */
    protected $bundle;

    /**
     * @var string|null
     */
    protected $controller;

    /**
     * @var string|null
     */
    protected $action;

    /**
     * @var string|null
     */
    protected $subcontext;

    public function getCreationTime(): ?\DateTimeInterface
    {
        return $this->getCreatedAt();
    }

    public function setId(string $id): void
    {
        $this->id = $id;
        $this->_uniqueIdentifier = $id;
    }

    public function setLanguageId(?string $languageId): void
    {
        $this->languageId = $languageId;
    }

    public function getLanguageId(): ?string
    {
        return $this->languageId;
    }

    public function setSalesChannelId(?string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }

    public function getSalesChannelId(): ?string
    {
        return $this->salesChannelId;
    }

    public function setBundle(?string $bundle): void
    {
        $this->bundle = $bundle;
    }

    public function getBundle(): ?string
    {
        return $this->bundle;
    }

    public function setController(?string $controller): void
    {
        $this->controller = $controller;
    }

    public function getController(): ?string
    {
        return $this->controller;
    }

    public function setAction(?string $action): void
    {
        $this->action = $action;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setSubcontext(?string $subcontext): void
    {
        $this->subcontext = $subcontext;
    }

    public function getSubcontext(): ?string
    {
        return $this->subcontext;
    }
}