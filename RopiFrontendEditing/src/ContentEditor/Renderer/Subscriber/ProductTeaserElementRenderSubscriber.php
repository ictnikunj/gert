<?php declare(strict_types=1);

namespace Ropi\FrontendEditing\ContentEditor\Renderer\Subscriber;

use Ropi\FrontendEditing\ContentEditor\Renderer\Events\ContentElementRenderEvent;
use Shopware\Core\Content\Product\Exception\ProductNotFoundException;
use Shopware\Core\Content\Product\SalesChannel\Detail\AbstractProductDetailRoute;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Exception\InvalidUuidException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;

class ProductTeaserElementRenderSubscriber implements EventSubscriberInterface
{

    /**
     * @var AbstractProductDetailRoute
     */
    private $productRoute;

    public function __construct(AbstractProductDetailRoute $productRoute)
    {
        $this->productRoute = $productRoute;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ContentElementRenderEvent::getNameForContentElementType('RopiFrontendEditing/product-teaser') => 'onRender'
        ];
    }

    public function onRender(ContentElementRenderEvent $event): void
    {
        $productId = $event->getParameters()['data']['configuration']['product']['value'] ?? '';

        $event->setParameter('product', $this->loadProduct($productId, $event->getSalesChannelContext()));
    }

    private function loadProduct(string $productId, SalesChannelContext $salesChannelContext): ?SalesChannelProductEntity
    {
        if (!$productId) {
            return null;
        }

        $criteria = (new Criteria())
            ->addAssociation('manufacturer.media')
            ->addAssociation('options.group')
            ->addAssociation('properties.group')
            ->addAssociation('mainCategories.category');

        $criteria
            ->getAssociation('media')
            ->addSorting(new FieldSorting('position'));


        try {
            $result = $this->productRoute->load($productId, new Request(), $salesChannelContext, $criteria);

            return $result->getProduct();
        } catch (ProductNotFoundException $productNotFoundException) {
            // Fail silently
        } catch (InvalidUuidException $invalidUuidException) {
            // Fail silently
        }

        return null;
    }
}
