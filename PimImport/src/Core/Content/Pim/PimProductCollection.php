<?php declare(strict_types=1);

namespace PimImport\Core\Content\Pim;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\Struct\ArrayEntity;

/**
 * @method void                 add(ArrayEntity $entity)
 * @method void                 set(string $key, ArrayEntity $entity)
 * @method ArrayEntity[]        getIterator()
 * @method ArrayEntity[]        getElements()
 * @method ArrayEntity|null     get(string $key)
 * @method ArrayEntity|null     first()
 * @method ArrayEntity|null     last()
 */
class PimProductCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ArrayEntity::class;
    }
}
