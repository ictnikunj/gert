<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Custom\Aggregate\ProductDownloadTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(ProductDownloadTranslationEntity $entity)
 * @method void              set(string $key, ProductDownloadTranslationEntity $entity)
 * @method ProductDownloadTranslationEntity[]    getIterator()
 * @method ProductDownloadTranslationEntity[]    getElements()
 * @method ProductDownloadTranslationEntity|null get(string $key)
 * @method ProductDownloadTranslationEntity|null first()
 * @method ProductDownloadTranslationEntity|null last()
 */
class ProductDownloadTranslationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ProductDownloadTranslationEntity::class;
    }
}
