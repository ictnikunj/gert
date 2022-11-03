<?php declare(strict_types=1);

namespace MoorlCmsShopTheLook;

use Shopware\Core\Framework\Plugin;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class MoorlCmsShopTheLook extends Plugin
{
    public const NAME = 'MoorlCmsShopTheLook';
    public const DATA_CREATED_AT = '2003-03-03 23:0:02.000';
    public const PLUGIN_TABLES = [];
    public const SHOPWARE_TABLES = [
        'cms_page',
        'cms_page_translation',
        'cms_section',
        'cms_block',
        'category',
        'product'
    ];
}
