<?php declare(strict_types=1);

namespace MoorlFormBuilder\Data;

use MoorlFormBuilder\Core\Event\CmsFormEvent;
use MoorlFormBuilder\MoorlFormBuilder;
use MoorlFoundation\Core\System\DataExtension;
use MoorlFoundation\Core\System\DataInterface;
use Doctrine\DBAL\Connection;

class Data extends DataExtension implements DataInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getTables(): ?array
    {
        return array_merge(
            $this->getShopwareTables(),
            $this->getPluginTables()
        );
    }

    public function getShopwareTables(): ?array
    {
        return MoorlFormBuilder::SHOPWARE_TABLES;
    }

    public function getPluginTables(): ?array
    {
        return MoorlFormBuilder::PLUGIN_TABLES;
    }

    public function getPluginName(): string
    {
        return MoorlFormBuilder::NAME;
    }

    public function getCreatedAt(): string
    {
        return MoorlFormBuilder::DATA_CREATED_AT;
    }

    public function getName(): string
    {
        return 'data';
    }

    public function getType(): string
    {
        return 'data';
    }

    public function getPath(): string
    {
        return __DIR__;
    }

    public function process(): void
    {
        $configKey = 'core.basicInformation.activeCaptchasV2';

        $query = $this->connection->executeQuery('SELECT * FROM `system_config` WHERE `configuration_key` = ?', [$configKey])->fetchAssociative();
        if ($query !== false) {
            $configurationValue = json_decode($query['configuration_value'], true);
            if (!empty($configurationValue['_value']['moorlCaptcha'])) {
                return;
            }
            $configurationValue['_value']['moorlCaptcha'] = [
                'name' => 'moorlCaptcha',
                'isActive' => false,
                'config' => [
                    'captchaFont' => '',
                    'captchaHeight' => '',
                    'captchaWidth' => '',
                    'totalCharacters' => '',
                    'possibleLetters' => '',
                    'randomDots' => '',
                    'randomLines' => '',
                    'textColor' => '',
                    'noiseColor' => '',
                    'backgroundColor' => ''
                ]
            ];
            $this->connection->executeUpdate('UPDATE `system_config` SET `configuration_value` = :configuration_value WHERE `id` = :id;', [
                'configuration_value' => json_encode($configurationValue),
                'id' => $query['id']
            ]);
        }
    }

    public function getLocalReplacers(): array
    {
        return [
            '{MAIL_TEMPLATE_MAIL_SEND_ACTION}' => MoorlFormBuilder::MAIL_TEMPLATE_MAIL_SEND_ACTION,
            '{CMS_FORM_EVENT}' => CmsFormEvent::EVENT_NAME
        ];
    }
}
