<?php

declare(strict_types=1);

namespace Sisi\PluginTemplate6\Resources\snippet\en_GB;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

// phpcs:disable Squiz.Classes.ValidClassName

/**
 * Class SnippetFile_en_GB
 *
 * @package Sisi\ElectronicsTheme\Resources\snippet\en_GB
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 */
class SnippetFile_en_GB implements SnippetFileInterface
{
    public function getName(): string
    {
        return 'messages.en-GB';
    }

    public function getPath(): string
    {
        return __DIR__ . '/messages.en-GB.json';
    }

    public function getIso(): string
    {
        return 'en-GB';
    }

    public function getAuthor(): string
    {
        return 'signundsinn GmbH';
    }

    public function isBase(): bool
    {
        return false;
    }
}
// phpcs:enable
