<?php declare(strict_types=1);

namespace MoorlFormBuilder\Core\Twig;

use MoorlFormBuilder\Core\Service\FormService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class GetFileWhitelistExtension extends AbstractExtension
{
    /**
     * @var FormService
     */
    private $formService;

    public function __construct(
        FormService $formService
    )
    {
        $this->formService = $formService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('moorlFormBuilderFileWhitelist', [$this, 'moorlFormBuilderFileWhitelist']),
        ];
    }

    public function moorlFormBuilderFileWhitelist(?string $mediaFileExtensions = null): array
    {
        $whitelist = $this->formService->extendFileTypeWhitelist(null);

        if ($mediaFileExtensions) {
            $mediaFileExtensions = explode(",", $mediaFileExtensions);
            $mediaFileExtensions = array_map('trim', $mediaFileExtensions);

            $whitelist = array_intersect($whitelist, $mediaFileExtensions);
        }

        $whitelist = array_map(function ($item) {
            return sprintf(".%s", $item);
        }, $whitelist);

        return $whitelist;
    }
}
