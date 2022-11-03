<?php declare(strict_types=1);

namespace PimImportCron\Service\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class RelatedCronTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'pimImportCron.related_product_cron_task';
    }

    public static function getDefaultInterval(): int
    {
        return 60*60*18; // 17:30 Hour
    }
}
