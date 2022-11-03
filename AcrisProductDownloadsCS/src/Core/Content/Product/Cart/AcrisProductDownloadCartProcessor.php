<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Core\Content\Product\Cart;

use Acris\ProductDownloads\Components\ProductDownloadService;
use Acris\ProductDownloads\Custom\ProductDownloadCollection;
use Acris\ProductDownloads\Custom\ProductLinkCollection;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartDataCollectorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class AcrisProductDownloadCartProcessor implements CartDataCollectorInterface
{
    /**
     * @var SystemConfigService
     */
    private $configService;
    private ProductDownloadService $productDownloadService;

    public function __construct(
        SystemConfigService $configService,
        ProductDownloadService $productDownloadService
    ) {
        $this->configService = $configService;
        $this->productDownloadService = $productDownloadService;
    }

    public function collect(
        CartDataCollection $data,
        Cart $original,
        SalesChannelContext $context,
        CartBehavior $behavior
    ): void {
        $loadActive = $this->configService->get('AcrisProductDownloadsCS.config.loadDataCheckout');
        if($loadActive !== "load") {
            return;
        }

        $lineItems = $original
            ->getLineItems()
            ->filterFlatByType(LineItem::PRODUCT_LINE_ITEM_TYPE);

        foreach ($lineItems as $lineItem) {
            $id = $lineItem->getReferencedId();
            $key = 'product-' . $id;
            $product = $data->get($key);

            if ($product instanceof SalesChannelProductEntity) {
                $this->productDownloadService->checkLanguageForProduct($product, $context->getContext()->getLanguageId(), $context);
                $this->productDownloadService->checkLanguageForProductLinks($product, $context->getContext()->getLanguageId(), $context);
                $lineItem->setPayloadValue('acrisDownloads', $this->convertPayload($product->getExtension('acrisDownloads')));
                $lineItem->setPayloadValue('acrisLinks', $this->convertLinkPayload($product->getExtension('acrisLinks')));
            }
        }
    }

    private function convertPayload(?ProductDownloadCollection $productDownloadCollection): array
    {
        if(!$productDownloadCollection instanceof ProductDownloadCollection) {
            return [];
        }

        $productDownloadArray = [];
        foreach ($productDownloadCollection->getElements() as $key => $productDownloadEntity) {
            $productDownloadArray[$key] = $productDownloadEntity->getTranslated();
            $productDownloadArray[$key]['mediaId'] = $productDownloadEntity->getMediaId();
            $productDownloadArray[$key]['media'] = $productDownloadEntity->getMedia();
            $productDownloadArray[$key]['languageIds'] = $productDownloadEntity->getLanguageIds();
            $productDownloadArray[$key]['position'] = $productDownloadEntity->getPosition();
        }
        return $productDownloadArray;
    }

    private function convertLinkPayload(?ProductLinkCollection $productLinkCollection): array
    {
        if(!$productLinkCollection instanceof ProductLinkCollection) {
            return [];
        }

        $productLinkArray = [];
        foreach ($productLinkCollection->getElements() as $key => $productLinkEntity) {
            $productLinkArray[$key] = $productLinkEntity->getTranslated();
            $productLinkArray[$key]['languageIds'] = $productLinkEntity->getLanguageIds();
            $productLinkArray[$key]['position'] = $productLinkEntity->getPosition();
        }
        return $productLinkArray;
    }
}
