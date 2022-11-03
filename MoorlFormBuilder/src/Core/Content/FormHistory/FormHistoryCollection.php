<?php declare(strict_types=1);

namespace MoorlFormBuilder\Core\Content\FormHistory;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                       add(FormHistoryEntity $entity)
 * @method void                       set(string $key, FormHistoryEntity $entity)
 * @method FormHistoryEntity[]    getIterator()
 * @method FormHistoryEntity[]    getElements()
 * @method FormHistoryEntity|null get(string $key)
 * @method FormHistoryEntity|null first()
 * @method FormHistoryEntity|null last()
 */
class FormHistoryCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return FormHistoryEntity::class;
    }
}
