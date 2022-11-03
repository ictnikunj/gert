<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Core\Content\Cms\DataResolver\Element;

use Acris\ProductDownloads\Core\Content\Cms\SalesChannel\Struct\ProductStruct;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\EntityResolverContext;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Content\Product\Cms\AbstractProductDetailCmsElementResolver;
use Shopware\Core\Content\Product\SalesChannel\Detail\ProductConfiguratorLoader;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;


class ProductResolver extends AbstractProductDetailCmsElementResolver
{
    /**
     * @var ProductConfiguratorLoader
     */
    private $configuratorLoader;

    public function __construct(
        ProductConfiguratorLoader $configuratorLoader
    ) {
        $this->configuratorLoader = $configuratorLoader;
    }

    public function getType(): string
    {
        return 'acris-product';
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $productStructure = new ProductStruct();
        $slot->setData($productStructure);

        $config = $slot->getFieldConfig();
        $productConfig = $config->get('product');

        if ($productConfig === null) {
            return;
        }

        $product = null;

        if ($productConfig->isMapped() && $resolverContext instanceof EntityResolverContext) {
            $product = $this->resolveEntityValue($resolverContext->getEntity(), $productConfig->getValue());
        }

        if ($productConfig->isStatic()) {
            $product = $this->getSlotProduct($slot, $result, $productConfig->getValue());
        }

        /** @var SalesChannelProductEntity|null $product */
        if ($product !== null) {
            $productStructure->setProduct($product);
            $productStructure->setProductId($product->getId());
            $productStructure->setConfiguratorSettings($this->configuratorLoader->load($product, $resolverContext->getSalesChannelContext()));
        }
    }

}
