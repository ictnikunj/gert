<?php declare(strict_types=1);

namespace Ict\ArticleUrl;

use Shopware\Core\Framework\Plugin;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class IctArticleUrl extends Plugin
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

    }
}
