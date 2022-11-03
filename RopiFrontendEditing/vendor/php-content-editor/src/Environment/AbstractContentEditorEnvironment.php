<?php
namespace Ropi\ContentEditor\Environment;

use Ropi\ContentEditor\Environment\Exception\RequestBodyParseException;

abstract class AbstractContentEditorEnvironment implements ContentEditorEnvironmentInterface
{
    /**
     * @var array|null
     */
    protected $parsedRequestBody;

    public function getRequestedVersionId(): string
    {
        return isset($_GET['ropi-content-editor-version'])
               && is_string($_GET['ropi-content-editor-version'])
               && trim($_GET['ropi-content-editor-version'])
               ? trim($_GET['ropi-content-editor-version'])
               : '';
    }

    public function getBreakpoints(): array
    {
        $breakpoints = [];

        $breakpoints['xl'] = [
            'name' => 'Desktop',
            'key' => 'xl',
            'width' => '1200px',
            'inheritsFrom' => null
        ];

        $breakpoints['lg'] = [
            'name' => 'Tablet Landscape',
            'key' => 'lg',
            'width' => '992px',
            'inheritsFrom' => 'xl'
        ];

        $breakpoints['md'] = [
            'name' => 'Tablet Portrait',
            'key' => 'md',
            'width' => '768px',
            'inheritsFrom' => 'lg'
        ];

        $breakpoints['sm'] = [
            'name' => 'Mobile Landscape',
            'key' => 'sm',
            'width' => '576px',
            'inheritsFrom' => 'md'
        ];

        $breakpoints['xs'] = [
            'name' => 'Mobile Portrait',
            'key' => 'xs',
            'width' => '320px',
            'inheritsFrom' => 'sm'
        ];

        return $breakpoints;
    }

    /**
     * @throws RequestBodyParseException
     */
    public function getParsedRequestBody(): array
    {
        if (is_array($this->parsedRequestBody)) {
            return $this->parsedRequestBody;
        }

        $requestBody = trim(file_get_contents('php://input'));
        if (!$requestBody) {
            return [];
        }

        $payload = json_decode($requestBody, true);
        if (!is_array($payload)) {
            throw new RequestBodyParseException(
                'Failed to parse request body',
                1556454658
            );
        }

        if (empty($payload)) {
            throw new RequestBodyParseException(
                'Request body is empty',
                1572012443
            );
        }

        $this->parsedRequestBody = $payload;

        return $payload;
    }
}
