<?php declare(strict_types=1);

namespace MoorlFormBuilder\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Migration\InheritanceUpdaterTrait;

class Migration1649840360Appointment extends MigrationStep
{
    use InheritanceUpdaterTrait;

    public function getCreationTimestamp(): int
    {
        return 1649840360;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `moorl_form_appointment` (
    `id` BINARY(16) NOT NULL,
    `moorl_form_id` BINARY(16),
    `product_id` BINARY(16),
    `order_id` BINARY(16),
    `customer_id` BINARY(16),
    `sales_channel_id` BINARY(16),
    `form_element` VARCHAR(255) NOT NULL,
    `note` longtext,
    `active` tinyint(4),
    `start` DATETIME(3) NOT NULL,
    `end` DATETIME(3),
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3),
    
    PRIMARY KEY (`id`),
    
    CONSTRAINT `fk.moorl_form_appointment.product_id` 
        FOREIGN KEY (`product_id`)
        REFERENCES `product` (`id`) 
        ON DELETE SET NULL ON UPDATE CASCADE,
    
    CONSTRAINT `fk.moorl_form_appointment.order_id` 
        FOREIGN KEY (`order_id`)
        REFERENCES `order` (`id`) 
        ON DELETE SET NULL ON UPDATE CASCADE,
    
    CONSTRAINT `fk.moorl_form_appointment.customer_id` 
        FOREIGN KEY (`customer_id`)
        REFERENCES `customer` (`id`) 
        ON DELETE SET NULL ON UPDATE CASCADE,
        
    CONSTRAINT `fk.moorl_form_appointment.moorl_form_id` 
        FOREIGN KEY (`moorl_form_id`) 
        REFERENCES `moorl_form` (`id`) 
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
SQL;
        $connection->executeUpdate($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
