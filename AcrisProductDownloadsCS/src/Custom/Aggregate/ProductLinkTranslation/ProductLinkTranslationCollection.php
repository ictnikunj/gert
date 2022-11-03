<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Custom\Aggregate\ProductLinkTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(ProductLinkTranslationEntity $entity)
 * @method void              set(string $key, ProductLinkTranslationEntity $entity)
 * @method ProductLinkTranslationEntity[]    getIterator()
 * @method ProductLinkTranslationEntity[]    getElements()
 * @method ProductLinkTranslationEntity|null get(string $key)
 * @method ProductLinkTranslationEntity|null first()
 * @method ProductLinkTranslationEntity|null last()
 */
class ProductLinkTranslationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ProductLinkTranslationEntity::class;
    }
}
