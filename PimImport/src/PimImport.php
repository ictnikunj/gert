<?php declare(strict_types=1);

namespace PimImport;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Shopware\Core\Framework\Plugin;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class PimImport extends Plugin
{
    public function uninstall(UninstallContext $uninstallContext): void
    {
        if ($uninstallContext->keepUserData()) {
            parent::uninstall($uninstallContext);
            return;
        }

        try {
            $connection = $this->container->get(Connection::class);
            $connection->executeStatement('DROP TABLE pim_product');
            $connection->executeStatement('DROP TABLE pim_category');

            $connection->executeStatement('DELETE FROM system_config WHERE configuration_key LIKE "%PimImport.config%"');

            $connection->executeStatement("DELETE FROM `scheduled_task` WHERE `scheduled_task`.`name` = 'pimImport.main_product_cron_task';");
            $connection->executeStatement("DELETE FROM `scheduled_task` WHERE `scheduled_task`.`name` = 'pimImport.addon_product_cron_task';");
            $connection->executeStatement("DELETE FROM `scheduled_task` WHERE `scheduled_task`.`name` = 'pimImport.related_product_cron_task';");
            $connection->executeStatement("DELETE FROM `scheduled_task` WHERE `scheduled_task`.`name` = 'pimImport.sub_product_cron_task';");
            $connection->executeStatement("DELETE FROM `scheduled_task` WHERE `scheduled_task`.`name` = 'pimImport.category_cron_task';");
            $connection->executeStatement("DELETE FROM `scheduled_task` WHERE `scheduled_task`.`name` = 'pimImport.product_property_cron_task';");
            $connection->executeStatement("DELETE FROM `scheduled_task` WHERE `scheduled_task`.`name` = 'pimImport.property_cron_task';");
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
        }
    }
}
