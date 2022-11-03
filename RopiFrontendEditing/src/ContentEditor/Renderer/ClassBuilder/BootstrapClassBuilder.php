<?php declare(strict_types=1);

namespace Ropi\FrontendEditing\ContentEditor\Renderer\ClassBuilder;

use Ropi\ContentEditor\Environment\ContentEditorEnvironmentInterface;

class BootstrapClassBuilder implements ClassBuilderInterface
{
    private $contentEditorEnvironment;

    public function __construct(ContentEditorEnvironmentInterface $contentEditorEnvironment)
    {
        $this->contentEditorEnvironment = $contentEditorEnvironment;
    }

    public function buildPaddingClasses(array $config = []): array
    {
        $classes = [];

        foreach ($config as $positionKey => $paddingConfig) {
            $paddingConfig = $this->resolveInheritances($paddingConfig);

            foreach ($paddingConfig as $breakpointKey => $value) {
                $sizeNumber = $this->sizeToNumber($value);
                if (!is_int($sizeNumber)) {
                    continue;
                }

                if ($breakpointKey === 'xs') {
                    $classes[] = "p{$positionKey}-" . $sizeNumber;
                } else {
                    $classes[] = "p{$positionKey}-{$breakpointKey}-" . $sizeNumber;
                }
            }
        }

        return array_unique($classes);
    }

    public function buildColumnClasses(array $config = []): array
    {
        $colClasses = [];

        // Resolve highest number of columns within a breakpoint
        $maxColumns = 0;
        foreach ($config as $columnDefinition) {
            $proportions = explode('-', $columnDefinition);
            if (count($proportions) > $maxColumns) {
                $maxColumns = count($proportions);
            }
        }

        $config = $this->resolveInheritances($config);
        foreach ($config as $breakpointKey => $columnDefinition) {
            $proportions = explode('-', $columnDefinition);
            $numProportions = array_sum($proportions);

            $breakpointColClasses = [];
            foreach ($proportions as $proportion) {
                $proportion = intval($proportion);
                if ($proportion <= 0) {
                    continue;
                }

                $colWidth = intval(12 / $numProportions * $proportion);

                if ($breakpointKey === 'xs') {
                    $breakpointColClasses[] = "col-{$colWidth}";
                } else {
                    $breakpointColClasses[] = "col-{$breakpointKey}-{$colWidth}";
                }
            }

            $breakpointColClasses = array_unique($breakpointColClasses);

            for ($i = 0; $i < $maxColumns; $i++) {
                $colClasses[$i][] = $breakpointColClasses[$i % count($breakpointColClasses)];
            }
        }

        return $colClasses;
    }

    protected function resolveInheritances($breakPointSpecificConfig): array
    {
        if (!is_array($breakPointSpecificConfig)) {
            return [];
        }

        $breakpoints = $this->contentEditorEnvironment->getBreakpoints();

        $resolvedConfig = [];

        foreach ($breakPointSpecificConfig as $breakpointKey => $value) {
            $loop = 0;

            $currentBreakpointKey = $breakpointKey;

            while ($value === 'inherit') {
                if ($loop++ > count($breakpoints)) {
                    // Avoid infinite loops on broken config
                    break;
                }

                $inheritsFrom = $breakpoints[$currentBreakpointKey]['inheritsFrom'] ?? '';
                if (!$inheritsFrom) {
                    break;
                }

                $currentBreakpointKey = $inheritsFrom;
                $value = $breakPointSpecificConfig[$inheritsFrom] ?? '';
            }

            if (trim($value) === '' || $value === 'inherit') {
                continue;
            }

            $resolvedConfig[$breakpointKey] = $value;
        }

        return $resolvedConfig;
    }

    protected function sizeToNumber(string $size): ?int
    {
        switch (strtolower($size)) {
            case 'none':
                return 0;
            case 'xs':
                return 1;
            case 'sm':
                return 2;
            case 'md':
                return 3;
            case 'lg':
                return 4;
            case 'xl':
                return 5;
        }

        return null;
    }
}
