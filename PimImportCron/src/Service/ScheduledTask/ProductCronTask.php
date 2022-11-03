<?php declare(strict_types=1);

namespace PimImportCron\Service\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class ProductCronTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'pimImportCron.main_product_cron_task';
    }

    public static function getDefaultInterval(): int
    {
        return 60*60*14; // 14 Hour
    }
}
