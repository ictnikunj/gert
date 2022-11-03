<?php
namespace Ropi\ContentEditor\Renderer;

interface ContentElementRendererInterface
{

    public function render(string $elementType, array $elementNode): string;
}
