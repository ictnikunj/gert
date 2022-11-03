<?php declare(strict_types=1);

namespace PimImportCron\Service\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class PropertyCronTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'pimImportCron.property_cron_task';
    }

    public static function getDefaultInterval(): int
    {
        return 60*60*21; // 21 Hour
    }
}
