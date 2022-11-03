<?php declare(strict_types=1);

namespace MoorlCmsShopTheLook\Demo\Standard;

use MoorlCmsShopTheLook\MoorlCmsShopTheLook;
use MoorlFoundation\Core\System\DataExtension;
use MoorlFoundation\Core\System\DataInterface;

class DemoStandard extends DataExtension implements DataInterface
{
    public function getTables(): ?array
    {
        return array_merge(
            $this->getShopwareTables(),
            $this->getPluginTables()
        );
    }

    public function getShopwareTables(): ?array
    {
        return MoorlCmsShopTheLook::SHOPWARE_TABLES;
    }

    public function getPluginTables(): ?array
    {
        return MoorlCmsShopTheLook::PLUGIN_TABLES;
    }

    public function getPluginName(): string
    {
        return MoorlCmsShopTheLook::NAME;
    }

    public function getCreatedAt(): string
    {
        return MoorlCmsShopTheLook::DATA_CREATED_AT;
    }

    public function getName(): string
    {
        return 'standard';
    }

    public function getType(): string
    {
        return 'demo';
    }

    public function getPath(): string
    {
        return __DIR__;
    }

    public function getRemoveQueries(): array
    {
        return [];
    }
}
