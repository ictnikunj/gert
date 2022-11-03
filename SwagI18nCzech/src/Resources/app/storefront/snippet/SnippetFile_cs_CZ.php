<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagI18nCzech\Resources\app\storefront\snippet;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;
use SwagI18nCzech\SwagI18nCzech;

class SnippetFile_cs_CZ implements SnippetFileInterface
{
    public function getName(): string
    {
        return 'storefront.' . SwagI18nCzech::SWAG_I18N_LOCALE_CODE;
    }

    public function getPath(): string
    {
        return __DIR__ . '/' . $this->getName() . '.json';
    }

    public function getIso(): string
    {
        return SwagI18nCzech::SWAG_I18N_LOCALE_CODE;
    }

    public function getAuthor(): string
    {
        return 'Shopware Services';
    }

    public function isBase(): bool
    {
        return false;
    }
}
