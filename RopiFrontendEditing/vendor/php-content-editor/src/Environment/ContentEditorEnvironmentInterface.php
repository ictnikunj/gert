<?php
namespace Ropi\ContentEditor\Environment;

use Ropi\ContentEditor\Environment\Exception\RequestBodyParseException;

interface ContentEditorEnvironmentInterface
{

    public function getRequestedVersionId(): string;

    public function getBreakpoints(): array;

    /**
     * @throws RequestBodyParseException
     */
    public function getParsedRequestBody(): array;

    public function editorOpened(): bool;
}
