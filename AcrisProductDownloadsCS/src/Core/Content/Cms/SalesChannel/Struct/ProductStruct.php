<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Core\Content\Cms\SalesChannel\Struct;

use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Content\Property\PropertyGroupCollection;
use Shopware\Core\Framework\Struct\Struct;

class ProductStruct extends Struct
{
    /**
     * @var SalesChannelProductEntity|null
     */
    protected $product;

    /**
     * @var PropertyGroupCollection|null
     */
    protected $configuratorSettings;

    /**
     * @var string|null
     */
    protected $productId;

    public function getProduct(): ?SalesChannelProductEntity
    {
        return $this->product;
    }

    public function getConfiguratorSettings(): ?PropertyGroupCollection
    {
        return $this->configuratorSettings;
    }

    public function setConfiguratorSettings(?PropertyGroupCollection $configuratorSettings): void
    {
        $this->configuratorSettings = $configuratorSettings;
    }

    public function setProduct(SalesChannelProductEntity $product): void
    {
        $this->product = $product;
    }

    public function getProductId(): ?string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }

    /**
     * @var string|null
     */
    protected $productContent;

    public function getProductContent(): ?string
    {
        return $this->productContent;
    }

    public function setProductContent(string $productContent): void
    {
        $this->productContent = $productContent;
    }

    public function getApiAlias(): string
    {
        return 'acris_product';
    }

}
