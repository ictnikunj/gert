<?php
namespace Ropi\ContentEditor\Service;

use Ropi\ContentEditor\Service\Exception\InvalidDocumentContextInStructureException;

class DocumentContextService implements DocumentContextServiceInterface
{

    /**
     * @throws InvalidDocumentContextInStructureException
     */
    public function getDocumentContextFromStructure(array $structure): array
    {
        if (!isset($structure['meta']['context']) || !is_array($structure['meta']['context'])) {
            throw new InvalidDocumentContextInStructureException(
                'Property "data.meta.context" is missing or invalid in payload',
                1572012448
            );
        }

        return $structure['meta']['context'];
    }

    public function setDocumentContextForStructure(array $structure, array $newDocumentContext): array
    {
        $structure['meta']['context'] = $newDocumentContext;

        return $structure;
    }
}
