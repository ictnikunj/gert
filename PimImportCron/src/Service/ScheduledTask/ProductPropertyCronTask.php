<?php declare(strict_types=1);

namespace PimImportCron\Service\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class ProductPropertyCronTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'pimImportCron.product_property_cron_task';
    }

    public static function getDefaultInterval(): int
    {
        return 60*60*22; // 22 Hour
    }
}
