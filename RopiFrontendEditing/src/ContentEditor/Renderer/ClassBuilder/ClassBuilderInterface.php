<?php declare(strict_types=1);

namespace Ropi\FrontendEditing\ContentEditor\Renderer\ClassBuilder;

interface ClassBuilderInterface
{
    public function buildPaddingClasses(array $config = []): array;

    public function buildColumnClasses(array $config = []): array;
}
