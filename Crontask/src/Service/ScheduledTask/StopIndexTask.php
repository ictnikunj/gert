<?php declare(strict_types=1);

namespace Crontask\Service\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class StopIndexTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'crontask.stop_index_task';
    }

    public static function getDefaultInterval(): int
    {
        return 120; // 2 Min
    }
}
