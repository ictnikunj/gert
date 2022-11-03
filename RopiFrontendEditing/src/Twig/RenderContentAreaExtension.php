<?php declare(strict_types=1);

namespace Ropi\FrontendEditing\Twig;

use Ropi\ContentEditor\Facade\ContentEditorRenderFacade;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class RenderContentAreaExtension extends AbstractExtension
{

    /**
     * @var ContentEditorRenderFacade
     */
    protected $contentEditorRenderFacade;

    public function __construct(ContentEditorRenderFacade $contentEditorRenderFacade)
    {
        $this->contentEditorRenderFacade = $contentEditorRenderFacade;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction(
                'ropi_frontend_editing_render_content_area',
                [$this, 'renderContentArea'],
                [
                    'is_safe' => ['html']
                ]
            ),
        ];
    }

    /**
     * @throws \Ropi\ContentEditor\Facade\Exception\RenderException
     */
    public function renderContentArea(string $areaName, ?array $contentAreas = []): string
    {
        if (!is_array($contentAreas)) {
            return '';
        }

        return $this->contentEditorRenderFacade->renderContentArea($areaName, $contentAreas);
    }
}