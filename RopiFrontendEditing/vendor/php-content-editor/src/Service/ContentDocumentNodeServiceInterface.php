<?php
namespace Ropi\ContentEditor\Service;

interface ContentDocumentNodeServiceInterface
{

    public function mergeConfiguration(array $targetNode, array $sourceNode, bool $keepLanguageSpecificSettings = false): array;

    public function mergeContents(array $targetNode, array $sourceNode): array;

}
