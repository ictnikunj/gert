<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Custom;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(ProductDownloadEntity $entity)
 * @method void              set(string $key, ProductDownloadEntity $entity)
 * @method ProductDownloadEntity[]    getIterator()
 * @method ProductDownloadEntity[]    getElements()
 * @method ProductDownloadEntity|null get(string $key)
 * @method ProductDownloadEntity|null first()
 * @method ProductDownloadEntity|null last()
 */
class ProductDownloadCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ProductDownloadEntity::class;
    }
}
