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

class PropertiesService
{
    public function setSortedProperties(array &$fields, &$entitie, array $parameters): void
    {
        if ($entitie->getSortedProperties() === null && array_key_exists('propertyGroupSorter', $parameters)) {
            $properties = $entitie->getProperties();
            $sortedProperties = $parameters['propertyGroupSorter']->sort($properties);
            $entitie->setSortedProperties($sortedProperties);
            if ((method_exists($fields['channel'], 'setSortedProperties'))) {
                $fields['channel']->setSortedProperties($sortedProperties);
            }
        }
    }
}
