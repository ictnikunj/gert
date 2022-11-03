<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Core\Content\Product\SalesChannel\Listing;

use Acris\ProductDownloads\Components\ProductDownloadService;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ProductListingLoader extends \Shopware\Core\Content\Product\SalesChannel\Listing\ProductListingLoader
{
    private SystemConfigService $configService;

    private \Shopware\Core\Content\Product\SalesChannel\Listing\ProductListingLoader $parent;

    private ProductDownloadService $productDownloadService;

    public function __construct(
        \Shopware\Core\Content\Product\SalesChannel\Listing\ProductListingLoader $parent,
        SystemConfigService $configService,
        ProductDownloadService $productDownloadService
    ) {
        $this->configService = $configService;
        $this->parent = $parent;
        $this->productDownloadService = $productDownloadService;
    }

    public function load(Criteria $origin, SalesChannelContext $salesChannelContext): EntitySearchResult
    {
        $loadActive = $this->configService->get('AcrisProductDownloadsCS.config.loadDataProductBox');
        $linkLoadActive = $this->configService->get('AcrisProductDownloadsCS.config.linkLoadDataProductBox');
        if($loadActive !== "load" && $linkLoadActive !== "load") {
            return $this->parent->load($origin, $salesChannelContext);
        }

        if ($loadActive === "load") $this->productDownloadService->addProductAssociationCriteria($origin);
        if ($linkLoadActive === "load") $this->productDownloadService->addProductLinkAssociationCriteria($origin);

        $entitySearchResult = $this->parent->load($origin, $salesChannelContext);

        /** @var SalesChannelProductEntity $product */
        foreach ($entitySearchResult->getElements() as $product) {
            if ($loadActive === "load") $this->productDownloadService->checkLanguageForProduct($product, $salesChannelContext->getContext()->getLanguageId(), $salesChannelContext);
            if ($linkLoadActive === "load") $this->productDownloadService->checkLanguageForProductLinks($product, $salesChannelContext->getContext()->getLanguageId(), $salesChannelContext);
        }

        return $entitySearchResult;
    }
}
