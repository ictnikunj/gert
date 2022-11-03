<?php

namespace Sisi\Search\Service;

use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Sisi\Search\Service\ContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Content\Category\CategoryCollection;

class CategorieService
{
    public function strIndexCategorie(array $config): bool
    {
        if (!array_key_exists('categorien', $config)) {
            return true;
        }

        if ($config['categorien'] === '2') {
            return false;
        }
        return true;
    }

    public function getProductStreamsCategories(SalesChannelProductEntity $entitie): CategoryCollection
    {
        $streams = $entitie->getStreams()->getElements();
        $return = new CategoryCollection();
        foreach ($streams as $stream) {
            if ($stream->getCategories() !== null) {
                $collection = $stream->getCategories()->getElements();
                if (count($collection) > 0) {
                    foreach ($collection as $value) {
                        $return->add($value);
                    }
                }
            }
        }
        return $return;
    }

    public function getMergeCategories(
        CategoryCollection &$categorien,
        CategoryCollection $categoieStream
    ): void {
        $streamElements = $categoieStream->getElements();
        foreach ($streamElements as $elements) {
            if ($elements->getActive()) {
                $categorien->add($elements);
            }
        }
    }

    public function getCategoriesParent(
        SalesChannelProductEntity $entitie,
        ContainerInterface $container
    ): CategoryCollection {
        $categories = $entitie->getCategories();
        $haendler = $container->get('category.repository');
        $contextService = new ContextService();
        $context = $contextService->getContext();
        $criteria = new Criteria();
        $return = new CategoryCollection();
        foreach ($categories as $categorie) {
            $mainCategorieId = trim($categorie->getParentId());
            if ($mainCategorieId !== null && $mainCategorieId !== "") {
                $criteria->addFilter(new EqualsFilter('parentId', $mainCategorieId));
                $values = $haendler->search($criteria, $context)->getEntities();
                foreach ($values as $value) {
                    if (get_class($value) === 'Shopware\Core\Content\Category\CategoryEntity') {
                        $return->add($value);
                    }
                }
            }
        }
        return $return;
    }

    public function getAllCategories(ContainerInterface $container, string $startId): array
    {
        $haendler = $container->get('category.repository');
        $contextService = new ContextService();
        $context = $contextService->getContext();
        $return = [];
        $this->tree($haendler, $context, $startId, $return);
        return $return;
    }

    private function tree(EntityRepository $haendler, Context $context, string $id, array &$return): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('parentId', $id));
        $criteria->addFilter(new EqualsFilter('active', 1));
        $values = $haendler->search($criteria, $context)->getEntities();
        foreach ($values as $value) {
            $return[] = $value->getId();
            $this->tree($haendler, $context, $value->getId(), $return);
        }
    }
}
