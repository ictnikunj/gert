<?php declare(strict_types=1);

namespace MoorlFormBuilder\Demo\Standard;

use MoorlFormBuilder\MoorlFormBuilder;
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
