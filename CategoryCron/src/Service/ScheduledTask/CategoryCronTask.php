<?php declare(strict_types=1);

namespace CategoryCron\Service\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class CategoryCronTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'pim.category.cron.channel';
    }

    public static function getDefaultInterval(): int
    {
        return 3600; // 1 Hour
    }
}
