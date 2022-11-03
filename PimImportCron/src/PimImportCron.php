<?php declare(strict_types=1);

namespace PimImportCron;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class PimImportCron extends Plugin
{
    public function uninstall(UninstallContext $uninstallContext): void
    {
        /* Keep UserData? Then do nothing here */
        if ($uninstallContext->keepUserData()) {
            parent::uninstall($uninstallContext);
            return;
        }

        /**
         * @var Connection $connection
         */
        try {
            $connection = $this->container->get(Connection::class);
            $connection->executeStatement(
                "DELETE FROM `scheduled_task` WHERE `scheduled_task`.`name` = 'pimImport.main_product_cron_task';"
            );
            $connection->executeStatement(
                "DELETE FROM `scheduled_task` WHERE `scheduled_task`.`name` = 'pimImport.addon_product_cron_task';"
            );
            $connection->executeStatement(
                "DELETE FROM `scheduled_task` WHERE `scheduled_task`.`name` = 'pimImport.related_product_cron_task';"
            );
            $connection->executeStatement(
                "DELETE FROM `scheduled_task` WHERE `scheduled_task`.`name` = 'pimImport.sub_product_cron_task';"
            );
            $connection->executeStatement(
                "DELETE FROM `scheduled_task` WHERE `scheduled_task`.`name` = 'pimImport.product_property_cron_task';"
            );
        } catch (Exception|NotFoundExceptionInterface|ContainerExceptionInterface $e) {
        }
    }
}
