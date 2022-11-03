<?php declare(strict_types=1);

namespace Stutt\ArticleUrl;

use Shopware\Core\Framework\Plugin;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class StuttArticleUrl extends Plugin
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

    }
}
