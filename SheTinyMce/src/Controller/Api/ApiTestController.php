<?php

declare(strict_types=1);

namespace She\TinyMce\Controller\Api;

use GuzzleHttp\Client;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use She\TinyMce\Exception\InvalidApiKeyException;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"administration"})
 */
class ApiTestController
{
    private Client $httpClient;

    public function __construct(Client $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @Route(path="/api/v{version}/_action/she-api-test/verify")
     */
    public function check(RequestDataBag $dataBag): JsonResponse
    {
        $apiKey = $dataBag->get('SheTinyMce.config.apiKey');

        $onRedirect = static function (
            RequestInterface $request,
            ResponseInterface $response,
            UriInterface $uri
        ): void {
            if (strpos((string)$uri, 'invalid-api-key')) {
                throw new InvalidApiKeyException();
            }
        };

        try {
            $this->httpClient->request(
                'GET',
                sprintf('https://cdn.tiny.cloud/1/%s/tinymce/5/tinymce.min.js', $apiKey),
                [
                    'allow_redirects' => [
                        'max' => 10,
                        // allow at most 10 redirects.
                        'strict' => true,
                        // use "strict" RFC compliant redirects.
                        'referer' => true,
                        // add a Referer header
                        'protocols' => ['https'],
                        // only allow https URLs
                        'on_redirect' => $onRedirect,
                        'track_redirects' => true,
                    ],
                ]
            );

            return new JsonResponse([
                'success' => true,
            ]);
        } catch (InvalidApiKeyException $exception) {
            return new JsonResponse([
                'success' => false,
            ]);
        }
    }
}
