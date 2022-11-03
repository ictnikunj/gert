<?php
declare(strict_types=1);

namespace Acris\ProductDownloads\Core\Content\Product\Cms;

use Acris\ProductDownloads\Components\ProductDownloadService;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\Element\CmsElementResolverInterface;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ProductSliderCmsElementResolver extends \Shopware\Core\Content\Product\Cms\ProductSliderCmsElementResolver implements CmsElementResolverInterface
{
    private CmsElementResolverInterface $parent;

    private SystemConfigService $configService;

    private ProductDownloadService $productDownloadService;

    public function __construct(
        CmsElementResolverInterface $parent,
        ProductDownloadService $productDownloadService,
        SystemConfigService $configService
    )
    {
        $this->parent = $parent;
        $this->configService = $configService;
        $this->productDownloadService = $productDownloadService;
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        $criteriaCollection = $this->parent->collect($slot, $resolverContext);

        if(empty($criteriaCollection)) {
            return $criteriaCollection;
        }

        $loadActive = $this->configService->get('AcrisProductDownloadsCS.config.loadDataProductBox');
        $linkLoadActive = $this->configService->get('AcrisProductDownloadsCS.config.linkLoadDataCheckout');
        if($loadActive === "load" || $linkLoadActive === "load") {
            foreach ($criteriaCollection->all() as $criterias) {
                if(is_array($criterias)) {
                    foreach ($criterias as $criteria) {
                        if ($loadActive === "load") $this->productDownloadService->addProductAssociationCriteria($criteria);
                        if ($linkLoadActive === "load") $this->productDownloadService->addProductLinkAssociationCriteria($criteria);
                    }
                }
            }
        }

        return $criteriaCollection;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $this->parent->enrich($slot, $resolverContext, $result);

        $loadActive = $this->configService->get('AcrisProductDownloadsCS.config.loadDataProductBox');
        $linkLoadActive = $this->configService->get('AcrisProductDownloadsCS.config.linkLoadDataProductBox');
        if($loadActive !== "load" && $linkLoadActive !== "load") {
            return;
        }

        $products = $slot->getData()->getProducts();
        if (empty($products)) return;

        foreach ($products as $product) {
            if ($loadActive === "load") $this->productDownloadService->checkLanguageForProduct($product, $resolverContext->getSalesChannelContext()->getContext()->getLanguageId(), $resolverContext->getSalesChannelContext());
            if ($linkLoadActive === "load") $this->productDownloadService->checkLanguageForProductLinks($product, $resolverContext->getSalesChannelContext()->getContext()->getLanguageId(), $resolverContext->getSalesChannelContext());
        }
    }

}
