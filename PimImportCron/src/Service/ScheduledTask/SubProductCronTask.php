<?php declare(strict_types=1);

namespace PimImportCron\Service\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class SubProductCronTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'pimImportCron.sub_product_cron_task';
    }

    public static function getDefaultInterval(): int
    {
        return 60*60*20; // 18 Hour
    }
}
