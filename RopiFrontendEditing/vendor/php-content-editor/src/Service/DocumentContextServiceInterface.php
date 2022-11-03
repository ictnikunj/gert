<?php
namespace Ropi\ContentEditor\Service;

use Ropi\ContentEditor\Service\Exception\InvalidDocumentContextInStructureException;

interface DocumentContextServiceInterface
{

    /**
     * @throws InvalidDocumentContextInStructureException
     */
    public function getDocumentContextFromStructure(array $structure): array;

    public function setDocumentContextForStructure(array $structure, array $newDocumentContext): array;
}
