<?php declare(strict_types=1);

namespace MoorlFormBuilder\Core\Service;

use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class FbService
{
    private DefinitionInstanceRegistry $definitionInstanceRegistry;
    private SystemConfigService $systemConfigService;

    private ?Context $context;

    public function __construct(
        DefinitionInstanceRegistry $definitionInstanceRegistry,
        SystemConfigService $systemConfigService
    ) {
        $this->definitionInstanceRegistry   = $definitionInstanceRegistry;
        $this->systemConfigService          = $systemConfigService;

        $this->context = Context::createDefaultContext();
    }

    public function enrichSalesChannelProductCriteria(Criteria $criteria, string $salesChannelId): void
    {
        $criteria->addAssociation('forms');
    }

    public function overrideSalesChannelProducts(iterable $products, SalesChannelContext $salesChannelContext): void
    {
        /** @var SalesChannelProductEntity $product */
        foreach ($products as $product) {
            $this->overrideSalesChannelProduct($product, $salesChannelContext);
        }
    }

    public function overrideSalesChannelProduct(SalesChannelProductEntity $product, SalesChannelContext $salesChannelContext): void
    {

    }
}
