<?php

declare(strict_types=1);

namespace Sisi\PluginTemplate6\Resources\snippet\de_DE;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

// phpcs:disable Squiz.Classes.ValidClassName

/**
 * Class SnippetFile_de_DE
 *
 * @package Sisi\PluginTemplate6\Resources\snippet\de_DE
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 */
class SnippetFile_de_DE implements SnippetFileInterface
{
    public function getName(): string
    {
        return 'messages.de-DE';
    }

    public function getPath(): string
    {
        return __DIR__ . '/messages.de-DE.json';
    }

    public function getIso(): string
    {
        return 'de-DE';
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
