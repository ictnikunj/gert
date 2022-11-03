<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Custom\Aggregate\ProductDownloadTabTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(ProductDownloadTabTranslationEntity $entity)
 * @method void              set(string $key, ProductDownloadTabTranslationEntity $entity)
 * @method ProductDownloadTabTranslationEntity[]    getIterator()
 * @method ProductDownloadTabTranslationEntity[]    getElements()
 * @method ProductDownloadTabTranslationEntity|null get(string $key)
 * @method ProductDownloadTabTranslationEntity|null first()
 * @method ProductDownloadTabTranslationEntity|null last()
 */
class ProductDownloadTabTranslationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ProductDownloadTabTranslationEntity::class;
    }
}
