<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Core\Framework\DataAbstractionLayer\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class LanguageNotFoundByLocaleException extends ShopwareHttpException
{
    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        return 'CONTENT__LANGUAGE_NOT_FOUND';
    }
}
