<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Custom;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(ProductLinkEntity $entity)
 * @method void              set(string $key, ProductLinkEntity $entity)
 * @method ProductLinkEntity[]    getIterator()
 * @method ProductLinkEntity[]    getElements()
 * @method ProductLinkEntity|null get(string $key)
 * @method ProductLinkEntity|null first()
 * @method ProductLinkEntity|null last()
 */
class ProductLinkCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ProductLinkEntity::class;
    }
}
