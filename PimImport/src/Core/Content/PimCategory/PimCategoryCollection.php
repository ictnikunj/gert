<?php declare(strict_types=1);

namespace PimImport\Core\Content\PimCategory;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                add(ArrayEntity $entity)
 * @method void                set(string $key, ArrayEntity $entity)
 * @method ArrayEntity[]    getIterator()
 * @method ArrayEntity[]    getElements()
 * @method ArrayEntity|null get(string $key)
 * @method ArrayEntity|null first()
 * @method ArrayEntity|null last()
 */
class PimCategoryCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return PimCategoryEntity::class;
    }
}
