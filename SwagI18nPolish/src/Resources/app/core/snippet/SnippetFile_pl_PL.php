<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagI18nPolish\Resources\app\core\snippet;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;
use SwagI18nPolish\SwagI18nPolish;

class SnippetFile_pl_PL implements SnippetFileInterface
{
    public function getName(): string
    {
        return 'messages.' . SwagI18nPolish::SWAG_I18N_LOCALE_CODE;
    }

    public function getPath(): string
    {
        return __DIR__ . '/' . $this->getName() . '.json';
    }

    public function getIso(): string
    {
        return SwagI18nPolish::SWAG_I18N_LOCALE_CODE;
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
