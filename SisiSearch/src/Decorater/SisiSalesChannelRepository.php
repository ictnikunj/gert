<?php

declare(strict_types=1);

namespace Sisi\Search\Decorater;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityAggregationResultLoadedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEventFactory;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntitySearchResultLoadedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Field\AssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Read\EntityReaderInterface;
use Shopware\Core\Framework\DataAbstractionLayer\RepositorySearchDetector;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntityAggregatorInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearcherInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Feature;
use Shopware\Core\Framework\Struct\ArrayEntity;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelDefinitionInterface;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\Event\SalesChannelProcessCriteriaEvent;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Sisi\Search\ESIndexInterfaces\InterfaceCreateCriteria;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelEntityIdSearchResultLoadedEvent;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelEntitySearchResultLoadedEvent;

/**
 *
 * @SuppressWarnings(PHPMD)
 *
 */
class SisiSalesChannelRepository implements SalesChannelRepositoryInterface
{
    /**
     * @var EntityDefinition
     */
    protected $definition;

    /**
     * @var EntityReaderInterface
     */
    protected $reader;

    /**
     * @var EntitySearcherInterface
     */
    protected $searcher;

    /**
     * @var EntityAggregatorInterface
     */
    protected $aggregator;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var EventDispatcherInterface
     */
    protected $heandersalesRepository;

    private ?EntityLoadedEventFactory $eventFactory;
    /**
     *
     * @var InterfaceCreateCriteria
     */
    protected $createCriteria;


    protected SalesChannelRepositoryInterface $orginal;

    public function __construct(
        EntityDefinition $definition,
        EntityReaderInterface $reader,
        EntitySearcherInterface $searcher,
        EntityAggregatorInterface $aggregator,
        EventDispatcherInterface $eventDispatcher,
        SalesChannelRepositoryInterface $orginal
    ) {
        $this->definition = $definition;
        $this->reader = $reader;
        $this->searcher = $searcher;
        $this->aggregator = $aggregator;
        $this->eventDispatcher = $eventDispatcher;
        $this->orginal = $orginal;
    }

    /**
     * @deprecated tag:v6.5.0 - Will be removed,
     */
    public function setEntityLoadedEventFactory(EntityLoadedEventFactory $eventFactory): void
    {
        if ($this->eventFactory === null) {
            Feature::throwException('FEATURE_NEXT_16155', sprintf('Sales channel repository for definition %s requires the event factory as __construct parameter', $this->definition->getEntityName()));
        }

        $this->eventFactory = $eventFactory;
    }

    /**
     * @throws InconsistentCriteriaIdsException
     */
    public function search(Criteria $criteria, SalesChannelContext $salesChannelContext): EntitySearchResult
    {
        $criteria = clone $criteria;
        $filters = $criteria->getFilterFields();
        $key = array_search("strfixDynamic", $filters);
        if ($key) {
            $this->checkstrField($criteria);
            $this->processCriteria($criteria, $salesChannelContext);
            $aggregations = null;
            if ($criteria->getAggregations()) {
                $aggregations = $this->aggregate($criteria, $salesChannelContext);
            }
            if (!RepositorySearchDetector::isSearchRequired($this->definition, $criteria)) {
                $entities = $this->read($criteria, $salesChannelContext);
                return new EntitySearchResult($this->definition->getEntityName(), $entities->count(), $entities, $aggregations, $criteria, $salesChannelContext->getContext());
            }
            $ids = $this->doSearch($criteria, $salesChannelContext);
            if (empty($ids->getIds())) {
                $collection = $this->definition->getCollectionClass();
                return new EntitySearchResult($this->definition->getEntityName(), $ids->getTotal(), new $collection(), $aggregations, $criteria, $salesChannelContext->getContext());
            }

            $readCriteria = $criteria->cloneForRead($ids->getIds());
            $entities = $this->read($readCriteria, $salesChannelContext);
            $search = $ids->getData();
            /** @var Entity $element */
            foreach ($entities as $element) {
                if (!\array_key_exists($element->getUniqueIdentifier(), $search)) {
                    continue;
                }

                $data = $search[$element->getUniqueIdentifier()];
                unset($data['id']);

                if (empty($data)) {
                    continue;
                }

                $element->addExtension('search', new ArrayEntity($data));
            }
            $result = new EntitySearchResult($this->definition->getEntityName(), $ids->getTotal(), $entities, $aggregations, $criteria, $salesChannelContext->getContext());
            $result->addState(...$ids->getStates());
            $event = new EntitySearchResultLoadedEvent($this->definition, $result);
            $this->eventDispatcher->dispatch($event, $event->getName());
            $event = new SalesChannelEntitySearchResultLoadedEvent($this->definition, $result, $salesChannelContext);
            $this->eventDispatcher->dispatch($event, $event->getName());
            return  $result;
        } else {
             return $this->orginal->search($criteria, $salesChannelContext);
        }
    }

    private function checkstrField(&$criteria): void
    {
        $filters = $criteria->getFilters();
        $criteria->resetFilters();
        foreach ($filters as $filter) {
            if (method_exists($filter, 'getField')) {
                $field = $filter->getField();
                if ($field !== 'strfixDynamic') {
                    $criteria->addFilter($filter);
                }
            } else {
                $criteria->addFilter($filter);
            }
        }
    }

    public function aggregate(Criteria $criteria, SalesChannelContext $salesChannelContext): AggregationResultCollection
    {
        $filters = $criteria->getFilterFields();
        $key = array_search("strfixDynamic", $filters);
        if ($key) {
            $this->checkstrField($criteria);
        }
        return $this->orginal->aggregate($criteria, $salesChannelContext);
    }

    public function searchIds(Criteria $criteria, SalesChannelContext $salesChannelContext): IdSearchResult
    {
        $filters = $criteria->getFilterFields();
        $key = array_search("strfixDynamic", $filters);
        if ($key) {
            $this->checkstrField($criteria);
        }
        return $this->orginal->searchIds($criteria, $salesChannelContext);
    }

    private function read(Criteria $criteria, SalesChannelContext $salesChannelContext): EntityCollection
    {
        $criteria = clone $criteria;

        $entities = $this->reader->read($this->definition, $criteria, $salesChannelContext->getContext());

        return $entities;
    }

    private function doSearch(Criteria $criteria, SalesChannelContext $salesChannelContext): IdSearchResult
    {
        $result = $this->searcher->search($this->definition, $criteria, $salesChannelContext->getContext());

        $event = new SalesChannelEntityIdSearchResultLoadedEvent($this->definition, $result, $salesChannelContext);
        $this->eventDispatcher->dispatch($event, $event->getName());

        return $result;
    }

    private function processCriteria(Criteria $topCriteria, SalesChannelContext $salesChannelContext): void
    {

        if (!$this->definition instanceof SalesChannelDefinitionInterface) {
            return;
        }

        $queue = [
            ['definition' => $this->definition, 'criteria' => $topCriteria],
        ];

        $maxCount = 100;

        $processed = [];


        // process all associations breadth-first
        while (!empty($queue) && --$maxCount > 0) {
            $cur = array_shift($queue);

            /** @var EntityDefinition $definition */
            $definition = $cur['definition'];
            $criteria = $cur['criteria'];

            if (isset($processed[\get_class($definition)])) {
                continue;
            }

            if ($definition instanceof SalesChannelDefinitionInterface) {
                $definition->processCriteria($criteria, $salesChannelContext);
            }

            $processed[\get_class($definition)] = true;

            foreach ($criteria->getAssociations() as $associationName => $associationCriteria) {
                // find definition
                $field = $definition->getField($associationName);
                if (!$field instanceof AssociationField) {
                    continue;
                }
                $referenceDefinition = $field->getReferenceDefinition();
                $queue[] = ['definition' => $referenceDefinition, 'criteria' => $associationCriteria];
            }
        }
    }
}
