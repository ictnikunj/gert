<?php declare(strict_types=1);

namespace Crontask\Service\ScheduledTask;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;

class StopIndexTaskHandler extends ScheduledTaskHandler
{
    protected $scheduledTaskRepository;
    private $messageQueueStatsRepository;

    public function __construct(
        EntityRepositoryInterface $scheduledTaskRepository,
        EntityRepositoryInterface $messageQueueStatsRepository
    ) {
        $this->scheduledTaskRepository = $scheduledTaskRepository;
        $this->messageQueueStatsRepository = $messageQueueStatsRepository;
    }

    public static function getHandledMessages(): iterable
    {
        return [ StopIndexTask::class ];
    }

    public function run(): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new NotFilter(
                NotFilter::CONNECTION_AND,
                [
                    new EqualsFilter('size', 0),
                ]
            )
        );
        $criteria->addSorting(new FieldSorting('updatedAt', 'ASC'));
        $data = $this->messageQueueStatsRepository->search($criteria, Context::createDefaultContext())->count();
        if($data > 3){
            $removeData = $this->messageQueueStatsRepository->search($criteria, Context::createDefaultContext())->getElements();
            foreach ($removeData as $element){
                if($element->getName() != 'Crontask\Service\ScheduledTask\StopIndexTask'){
                    $UpdatedData = [
                        'id' => $element->getId(),
                        'size' => 0,
                    ];
                    $this->messageQueueStatsRepository->upsert([$UpdatedData], Context::createDefaultContext());
                }
            }
        }
    }
}
