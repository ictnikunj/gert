<?php declare(strict_types=1);

namespace MoorlFormBuilder\Core\Content\Form;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                       add(FormEntity $entity)
 * @method void                       set(string $key, FormEntity $entity)
 * @method FormEntity[]    getIterator()
 * @method FormEntity[]    getElements()
 * @method FormEntity|null get(string $key)
 * @method FormEntity|null first()
 * @method FormEntity|null last()
 */
class FormCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return FormEntity::class;
    }

    public function _getByProductId(string $productId): ?FormEntity
    {
        return $this->filter(function (FormEntity $formEntity) use ($productId) {
            return $formEntity->getProducts()->has($productId);
        })->first();
    }

    public function getByProductId(string $productId): ?FormEntity
    {
        return $this->filter(function (FormEntity $formEntity) use ($productId) {
            $c0 = ($formEntity->getProducts()->filter(function (ProductEntity $productEntity) use ($productId) {
                return ($productEntity->getChildren() && $productEntity->getChildren()->has($productId));
            }))->first();

            return ($formEntity->getProducts()->has($productId) || $c0);
        })->first();
    }

    public function getByAction(string $action): ?FormEntity
    {
        return $this->filter(function (FormEntity $formEntity) use ($action) {
            return ($formEntity->getAction() === $action);
        })->first();
    }

    public function getByTypeProductId(string $type, string $productId = null): ?FormEntity
    {
        return $this->filter(function (FormEntity $formEntity) use ($type, $productId) {
            if ($formEntity->getProducts()) {
                $c0 = ($formEntity->getProducts()->filter(function (ProductEntity $productEntity) use ($productId) {
                    return ($productEntity->getChildren() && $productEntity->getChildren()->has($productId));
                }))->first();

                $c1 = (!$productId || $formEntity->getProducts()->has($productId) || $c0);
                $c2 = ($formEntity->getType() === $type);

                return ($c1 && $c2);
            } else {
                return false;
            }
        })->first();
    }

    public function getByTypeProductProperty(string $type, string $property, $value): ?FormEntity
    {
        return $this->filter(function (FormEntity $formEntity) use ($type, $property, $value) {
            $c0 = ($formEntity->getProducts()->filter(function (ProductEntity $productEntity) use ($property, $value) {
                return ($productEntity->getChildren() && $productEntity->getChildren()->filterByProperty($property, $value)->count() > 0);
            }))->first();

            $c1 = ($formEntity->getProducts()->filterByProperty($property, $value)->count() > 0);
            $c2 = ($formEntity->getType() === $type);
            return (($c0 || $c1) && $c2);
        })->first();
    }

    public function filterByType(string $type): self
    {
        return $this->filter(function (FormEntity $formEntity) use ($type) {
            return $formEntity->getType() === $type;
        });
    }

    public function filterByTypeProductIds(string $type, array $productIds): self
    {
        return $this->filter(function (FormEntity $formEntity) use ($type, $productIds) {
            foreach ($productIds as $productId) {
                $c0 = ($formEntity->getProducts()->filter(function (ProductEntity $productEntity) use ($productId) {
                    return ($productEntity->getChildren() && $productEntity->getChildren()->has($productId));
                }))->first();

                $c1 = ($formEntity->getProducts()->has($productId));
                $c2 = ($formEntity->getType() === $type);
                if (($c0 || $c1) && $c2) {
                    return true;
                }
            }
        });
    }
}
