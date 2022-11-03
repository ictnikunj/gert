<?php
/**
 * NOTICE OF LICENSE
 *
 * @copyright  Copyright (c) 21.10.2020 brainstation GbR
 * @author     Marco Becker<marco@brainstation.de>
 */
declare(strict_types=1);

namespace BstFlipListingImage6\Storefront\Subscriber;

use BstFlipListingImage6\Struct\ConfigData;
use BstFlipListingImage6\Struct\Media;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Shopware\Core\Framework\Api\Context\SalesChannelApiSource;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Shopware\Storefront\Pagelet\Header\HeaderPageletLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Navigation implements EventSubscriberInterface
{
    const EXTENSION_NAME = 'BstFlipListingImage6';

    /** @var EntityRepositoryInterface */
    private $productRepository;

    /** @var SystemConfigService */
    private $systemConfigService;

    /** @var AbstractSalesChannelContextFactory */
    private $salesChannelContextFactory;

    /** @var LoggerInterface */
    private $logger;

    /** @var ConfigData */
    private $config;

    /** @var Bool */
    private $wishlistEnabled;

    /**
     * Frontend constructor.
     * @param SystemConfigService $systemConfigService
     * @param LoggerInterface $logger
     */
    public function __construct(
        EntityRepositoryInterface $productRepository,
        SystemConfigService $systemConfigService,
        AbstractSalesChannelContextFactory $salesChannelContextFactory,
        LoggerInterface $logger
    ) {
        $this->productRepository = $productRepository;
        $this->systemConfigService = $systemConfigService;
        $this->salesChannelContextFactory = $salesChannelContextFactory;
        $this->logger = $logger;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            HeaderPageletLoadedEvent::class => 'onPageLoaded',
            ProductPageLoadedEvent::class => 'onProductLoad',
            ProductEvents::PRODUCT_LOADED_EVENT => 'onProductsLoaded'
        ];
    }

    /**
     * @param EntityLoadedEvent $event
     */
    public function onProductsLoaded(EntityLoadedEvent $event): void
    {
        if ($event->getContext()->getSource() instanceof SalesChannelApiSource) {
            $salesChannelContext = $this->salesChannelContextFactory->create(Uuid::randomHex(), $event->getContext()->getSource()->getSalesChannelId());
            $this->wishlistEnabled = $this->systemConfigService->get('core.cart.wishlistEnabled', $salesChannelContext->getSalesChannel()->getId());

            if (!$this->config) {
                $this->config = new ConfigData($this->systemConfigService, $salesChannelContext);
            }

            /*if (!$this->config->enabled) {
                return;
            }*/

            /** @var ProductEntity $productEntity */
            foreach ($event->getEntities() as $productEntity) {
                $this->extendProductEntity($productEntity);
            }
        }
    }

    /**
     * @param SalesChannelContext $context
     * @param boolean $asArray
     * @return ConfigData
     */
    private function getConfigData(SalesChannelContext $context, $asArray = false)
    {
        if (!$this->config) {
            $this->config = new ConfigData($this->systemConfigService, $context);
        }

        if ($asArray) {
            return $this->config->getConfig();
        }

        return $this->config;
    }

    /**
     * @param HeaderPageletLoadedEvent $event
     */
    public function onPageLoaded(HeaderPageletLoadedEvent $event): void
    {
        $event->getPagelet()->addExtension(self::EXTENSION_NAME, $this->getConfigData($event->getSalesChannelContext()));
    }

    /**
     * @param ProductPageLoadedEvent $event
     */
    public function onProductLoad(ProductPageLoadedEvent $event): void
    {
        // configuration not saved already
        if (!$this->config || !$this->config->enabled) {
            return;
        }

        /** @var SalesChannelProductEntity $productEntity */
        $productEntity = $event->getPage()->getProduct();
        $this->extendProductEntity($productEntity);
    }

    /**
     * @param $productEntity
     * @throws \Shopware\Core\Framework\Uuid\Exception\InvalidUuidException
     */
    private function extendProductEntity($productEntity)
    {
        $imagePosition = ($this->config->imageNumber ? $this->config->imageNumber : 2);

        $this->config->setConfig(['wishlistEnabled' => $this->wishlistEnabled]);
        $productEntity->addExtension(self::EXTENSION_NAME . 'Config', $this->config);

        // check if images should be loaded from variant or parent product
        if (gettype($productEntity->getCover()) == 'object' && gettype($productEntity->getCover()->getProductId()) == 'string') {
            $loadProductId = $productEntity->getCover()->getProductId();
        } else {
            $loadProductId = $productEntity->getId();
        }

        $criteria = (new Criteria([$loadProductId]))
            ->addAssociation('media');
        $criteria->getAssociation('media')->addSorting(new FieldSorting('position'));

        $product = $this->productRepository->search($criteria, Context::createDefaultContext())->get($loadProductId);

        $productMedia = $product->getMedia()->getMedia();
        $amountMedia = count($productMedia);
        if ($amountMedia > 1) {
            if ($amountMedia < $imagePosition) {
                $imagePosition = $amountMedia;
            }

            $counter = 1;
            foreach ($productMedia as $media) {
                if ($counter == $imagePosition) {
                    // get struct for extension
                    $mediaStruct = new Media();
                    $mediaStruct->setMedia($media);

                    $productEntity->addExtension(self::EXTENSION_NAME, $mediaStruct);
                    break;
                }
                $counter++;
            }
        }
    }
}
