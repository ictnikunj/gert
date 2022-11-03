<?php declare(strict_types=1);

namespace CategoryCron\Core\Content\CategoryCron;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                add(CategoryCronSaleschannelEntity $entity)
 * @method void                set(string $key, CategoryCronSaleschannelEntity $entity)
 * @method CategoryCronSaleschannelEntity[]    getIterator()
 * @method CategoryCronSaleschannelEntity[]    getElements()
 * @method CategoryCronSaleschannelEntity|null get(string $key)
 * @method CategoryCronSaleschannelEntity|null first()
 * @method CategoryCronSaleschannelEntity|null last()
 */
class CategoryCronSaleschannelCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return CategoryCronSaleschannelEntity::class;
    }
}
