<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagI18nSpanish\Resources\app\core\snippet;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;
use SwagI18nSpanish\SwagI18nSpanish;

class SnippetFile_es_ES implements SnippetFileInterface
{
    public function getName(): string
    {
        return 'messages.' . SwagI18nSpanish::SWAG_I18N_LOCALE_CODE;
    }

    public function getPath(): string
    {
        return __DIR__ . '/' . $this->getName() . '.json';
    }

    public function getIso(): string
    {
        return SwagI18nSpanish::SWAG_I18N_LOCALE_CODE;
    }

    public function getAuthor(): string
    {
        return 'Shopware Services';
    }

    public function isBase(): bool
    {
        return true;
    }
}
