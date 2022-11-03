<?php

declare(strict_types=1);

namespace She\TinyMce\Components;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class TemplateDataExtension extends AbstractExtension implements GlobalsInterface
{
    private SystemConfigService $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    public function getGlobals(): array
    {
        return [
            'sheTinyMce' => [
                'config' => $this->systemConfigService->get('SheTinyMce.config'),
            ],
        ];
    }
}
