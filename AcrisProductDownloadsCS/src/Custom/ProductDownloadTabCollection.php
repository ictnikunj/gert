<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Custom;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(ProductDownloadTabEntity $entity)
 * @method void              set(string $key, ProductDownloadTabEntity $entity)
 * @method ProductDownloadTabEntity[]    getIterator()
 * @method ProductDownloadTabEntity[]    getElements()
 * @method ProductDownloadTabEntity|null get(string $key)
 * @method ProductDownloadTabEntity|null first()
 * @method ProductDownloadTabEntity|null last()
 */
class ProductDownloadTabCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ProductDownloadTabEntity::class;
    }
}
