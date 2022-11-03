<?php
namespace Ropi\ContentEditor\Facade;

use Ropi\ContentEditor\Facade\Exception\RenderException;
use Ropi\ContentEditor\Renderer\ContentElementRendererInterface;

class ContentEditorRenderFacade
{

    /**
     * @var ContentElementRendererInterface
     */
    private $contentElementRenderer;

    public function __construct(ContentElementRendererInterface $contentElementRenderer)
    {
        $this->contentElementRenderer = $contentElementRenderer;
    }

    public function getContentElementRenderer(): ContentElementRendererInterface
    {
        return $this->contentElementRenderer;
    }

    /**
     * @param $areaName
     * @param array $areaNodes
     * @return string
     * @throws RenderException
     */
    public function renderContentArea($areaName, array $areaNodes)
    {
        $renderedContent = '';

        foreach ($areaNodes as $areaNode) {
            if (!isset($areaNode['type'])) {
                throw new RenderException(
                    'Property "type" is missing for content node ' . json_encode($areaNode),
                    1572017984
                );
            }

            if ($areaNode['type'] !== 'area') {
                continue;
            }

            if ($areaNode['meta']['name'] !== $areaName) {
                continue;
            }

            if (!is_array($areaNode['children'])) {
                continue;
            }

            foreach ($areaNode['children'] as $childNode) {
                if ($childNode['type'] !== 'element') {
                    continue;
                }

                $renderedContent .= $this->renderContentElement($childNode['meta']['type'], $childNode);
            }
        }

        return $renderedContent;
    }

    /**
     * @param $elementType
     * @param $elementNode
     * @return string
     */
    protected function renderContentElement($elementType, $elementNode)
    {
        return $this->getContentElementRenderer()->render($elementType, $elementNode);
    }
}
