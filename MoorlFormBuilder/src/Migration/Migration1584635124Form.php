<?php declare(strict_types=1);

namespace MoorlFormBuilder\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Migration\InheritanceUpdaterTrait;

class Migration1584635124Form extends MigrationStep
{
    use InheritanceUpdaterTrait;

    public function getCreationTimestamp(): int
    {
        return 1584635124;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `moorl_form` (
    `id` BINARY(16) NOT NULL,
    `mail_template_id` BINARY(16),
    `media_folder_id` BINARY(16),
    `name` VARCHAR(255) COLLATE utf8mb4_unicode_ci,
    `action` VARCHAR(255) COLLATE utf8mb4_unicode_ci,
    `type` VARCHAR(255) COLLATE utf8mb4_unicode_ci,
    `email_receiver` VARCHAR(255) COLLATE utf8mb4_unicode_ci,
    `success_message` VARCHAR(255) COLLATE utf8mb4_unicode_ci,
    `redirect_to` VARCHAR(255) COLLATE utf8mb4_unicode_ci,
    `media_folder` VARCHAR(255) COLLATE utf8mb4_unicode_ci,
    `related_entity` VARCHAR(255) COLLATE utf8mb4_unicode_ci,
    `stylesheet` TEXT COLLATE utf8mb4_unicode_ci,
    `max_file_size` INT(11),
    `send_mail` TINYINT,
    `privacy` TINYINT,
    `active` TINYINT,
    `use_captcha` TINYINT,
    `use_trans` TINYINT,
    `send_copy` TINYINT,
    `insert_database` TINYINT,
    `locked` TINYINT,
    `bootstrap_grid` TINYINT,
    `label` JSON NULL,
    `data` JSON NULL,
    `custom_fields` JSON NULL,
    `created_at` DATETIME(3),
    `updated_at` DATETIME(3),
    PRIMARY KEY (`id`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;
SQL;

        $connection->executeUpdate($sql);

        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `moorl_form_product` (
    `moorl_form_id` binary(16) NOT NULL,
    `product_id` binary(16) NOT NULL,
    `product_version_id` binary(16) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    
    PRIMARY KEY (`moorl_form_id`,`product_id`,`product_version_id`),
    
    CONSTRAINT `fk.moorl_form_product.product_id__product_version_id` 
        FOREIGN KEY (`product_id`, `product_version_id`)
        REFERENCES `product` (`id`, `version_id`) ON DELETE CASCADE ON UPDATE CASCADE,
        
    CONSTRAINT `fk.moorl_form_product.moorl_form_id` 
        FOREIGN KEY (`moorl_form_id`) 
        REFERENCES `moorl_form` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeUpdate($sql);

        try {
            $this->updateInheritance($connection, 'product', 'forms');
        } catch (\Exception $exception) {

        }
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
