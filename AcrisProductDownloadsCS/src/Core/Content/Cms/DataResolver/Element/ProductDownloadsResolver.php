<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Core\Content\Cms\DataResolver\Element;

use Acris\ProductDownloads\Components\ProductDownloadService;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Content\Cms\DataResolver\Element\CmsElementResolverInterface;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Content\Product\SalesChannel\Detail\ProductConfiguratorLoader;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ProductDownloadsResolver extends ProductResolver
{
    /**
     * @var ProductConfiguratorLoader
     */
    private $configuratorLoader;

    private ProductDownloadService $productDownloadService;

    public function __construct (
        ProductConfiguratorLoader $configuratorLoader,
        ProductDownloadService $productDownloadService
    )
    {
        $this->configuratorLoader = $configuratorLoader;
        $this->productDownloadService = $productDownloadService;

        parent::__construct($configuratorLoader);
    }

    public function getType(): string
    {
        return 'acris-product-downloads';
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        $criteriaCollection = parent::collect($slot, $resolverContext);

        if(empty($criteriaCollection)) {
            return $criteriaCollection;
        }

        foreach ($criteriaCollection->all() as $criterias) {
            if(is_array($criterias)) {
                foreach ($criterias as $criteria) {
                    $this->productDownloadService->addProductAssociationCriteria($criteria);
                }
            }
        }

        return $criteriaCollection;
    }
}
