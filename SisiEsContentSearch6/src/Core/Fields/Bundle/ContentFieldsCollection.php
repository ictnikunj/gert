<?php

declare(strict_types=1);

namespace Sisi\SisiEsContentSearch6\Core\Fields\Bundle;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(ContentFieldsEntity $entity)
 * @method void              set(string $key, ContentFieldsEntity $entity)
 * @method ContentFieldsEntity[]    getIterator()
 * @method ContentFieldsEntity[]    getElements()
 * @method ContentFieldsEntity|null get(string $key)
 * @method ContentFieldsEntity|null first()
 * @method ContentFieldsEntity|null last()
 */
class ContentFieldsCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ContentFieldsEntity::class;
    }
}
