<?php declare(strict_types=1);

namespace PimImportCron\Service\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class MediaRedirectCronTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'pimImportCron.media_redirect_cron_task';
    }

    public static function getDefaultInterval(): int
    {
        return 1800; //30min
    }
}
