<?php

namespace Sisi\Search\Service;

use MyProject\Container;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionCollection;
use Shopware\Core\Content\Property\PropertyGroupEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SortingService
{
    public function sortDbQueryToES(EntitySearchResult &$result, array $hits): void
    {
        $entities = $result->getEntities();
        $sort = [];
        foreach ($entities as $key => $entity) {
            foreach ($hits as $index => $hit) {
                if ($key === $hit["_id"]) {
                    $sort[$index] = $entity->getId();
                }
            }
        }
        ksort($sort);
        $result->sortByIdArray($sort);
    }

    public function getProptertiesfilters(
        EntitySearchResult $result,
        ContainerInterface $container,
        array $config
    ): array {
        if (array_key_exists('filteronpageresult', $config)) {
            if ($config['filteronpageresult'] == 'yes') {
                return [];
            }
        }
        if (array_key_exists('filtertype', $config)) {
            if ($config['filtertype'] == 'yes') {
                return $this->getAllFilters($container);
            }
        }

        return $this->getRealtionsFilters($result, $config);
    }

    private function getAllFilters(ContainerInterface $container): array
    {
        $repository = $container->get('property_group.repository');
        $contextheandler = new ContextService();
        $criteria = new Criteria();
        $criteria->addAssociation('options');
        $context = $contextheandler->getContext();
        $entitiesPro = $repository->search($criteria, $context);
        $prupertiesGoup = $entitiesPro->getEntities();
        $collectionPro = [];
        foreach ($prupertiesGoup as $property) {
            if (!array_key_exists($property->getName(), $collectionPro)) {
                $collectionPro[$property->getName()][] = $property;
            }
        }

        return $collectionPro;
    }

    private function getRealtionsFilters(EntitySearchResult $result, array $config): array
    {
        $entities = $result->getEntities();
        $properties = [];
        $collection = [];
        foreach ($entities as $entity) {
            foreach ($entity->getSortedProperties() as $index => $property) {
                if (!array_key_exists($property->getName(), $collection)) {
                    $collection[$property->getName()] = new PropertyGroupOptionCollection();
                }
            }
        }
        foreach ($entities as $entity) {
            foreach ($entity->getSortedProperties() as $index => $property) {
                $options = $property->getOptions();
                foreach ($options as $option) {
                    $collection[$property->getName()]->add($option);
                }
            }
        }
        $this->mergeProperties($entities, $properties, $collection);
        $properties = $this->filter($config, $properties);
        return $properties;
    }

    private function filter(array $config, array $properties): array
    {
        $countFilter = 0;
        $filter = [];
        $return = [];
        if (array_key_exists('filterfilter', $config)) {
            if (!empty($config['filterfilter'])) {
                $filter = explode("\n", $config['filterfilter']);
                $countFilter = count($filter);
            }
        }
        if ($countFilter > 0) {
            foreach ($properties as $key => $property) {
                if (in_array($key, $filter)) {
                    $return[] = $property;
                }
            }
            return $return;
        }
        return $properties;
    }

    private function mergeProperties(EntityCollection $entities, array &$properties, array &$collection): void
    {
        $merker = [];
        foreach ($entities as $entity) {
            foreach ($entity->getSortedProperties() as $index => $property) {
                $id = $property->getId();
                if (!in_array($id, $merker)) {
                    $property->setOptions($collection[$property->getName()]);
                    $properties[$property->getName()][$index] = $property;
                    $merker[] = $id;
                }
            }
        }
    }

    public function getManufactory(EntitySearchResult $result, ContainerInterface $container, array $config): array
    {
        if (array_key_exists('filteronpageresult', $config)) {
            if ($config['filteronpageresult'] == 'yes') {
                return [];
            }
        }
        if (array_key_exists('filtertype', $config)) {
            if ($config['filtertype'] == 'yes') {
                return $this->getAllManufactory($container);
            }
        }
        return $this->getRelationManufactory($result);
    }

    private function getRelationManufactory(EntitySearchResult $result): array
    {
        $entities = $result->getEntities();
        $manufatory = [];
        foreach ($entities as $entity) {
            $manufacturer = $entity->getManufacturer();
            if ($manufacturer != null) {
                $manufatory[$manufacturer->getId()] = $entity->getManufacturer();
            }
        }
        return $manufatory;
    }

    private function getAllManufactory(ContainerInterface $container): array
    {
        $repository = $container->get('product_manufacturer.repository');
        $contextheandler = new ContextService();
        $context = $contextheandler->getContext();
        $criteria = new Criteria();
        $entitiesManu = $repository->search($criteria, $context);
        $manufatory = $entitiesManu->getEntities()->getElements();
        return $manufatory;
    }
}
