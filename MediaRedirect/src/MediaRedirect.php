<?php declare(strict_types=1);

namespace MediaRedirect;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class MediaRedirect extends Plugin
{
    public function uninstall(UninstallContext $uninstallContext): void
    {
        /* Keep UserData? Then do nothing here */
        if ($uninstallContext->keepUserData()) {
            return;
        }

        /**
         * @var Connection $connection
         */
        try {
            $connection = $this->container->get(Connection::class);
            $connection->executeStatement('DROP TABLE ict_media_redirect');
        } catch (Exception|NotFoundExceptionInterface|ContainerExceptionInterface $e) {
        }
    }
}
