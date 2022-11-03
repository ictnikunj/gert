<?php declare(strict_types=1);

namespace MediaRedirect\Core\Content\MediaRedirect;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                add(MediaRedirectEntity $entity)
 * @method void                set(string $key, MediaRedirectEntity $entity)
 * @method MediaRedirectEntity[]    getIterator()
 * @method MediaRedirectEntity[]    getElements()
 * @method MediaRedirectEntity|null get(string $key)
 * @method MediaRedirectEntity|null first()
 * @method MediaRedirectEntity|null last()
 */
class MediaRedirectCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return MediaRedirectEntity::class;
    }
}
