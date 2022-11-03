<?php
declare(strict_types=1);

namespace MoorlFormBuilder\Core\Twig;

use MoorlFormBuilder\Core\Service\FormService;
use MoorlFormBuilder\Core\Content\Form\FormEntity;
use Shopware\Core\Framework\Adapter\Twig\TemplateFinder;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FormExtension extends AbstractExtension
{
    /**
     * @var TemplateFinder
     */
    private $finder;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var FormService
     */
    private $formService;

    public function __construct(
        TemplateFinder $finder,
        FormService $formService
    ) {
        $this->finder = $finder;
        $this->formService = $formService;
        $this->context = Context::createDefaultContext();
    }

    public function getTokenParsers(): array
    {
        return [
            new FormTokenParser()
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('moorl_get_form', [$this, 'getForm'], ['needs_context' => true]),
            new TwigFunction('moorl_get_date', [$this, 'getDate'])
        ];
    }

    public function getDate(?string $value = null): string
    {
        if (empty($value)) {
            return "";
        }

        $date = new \DateTimeImmutable($value);

        return $date->format("Y-m-d");
    }

    public function getFinder(): TemplateFinder
    {
        return $this->finder;
    }

    public function getForm(array $twigContext, string $action, Context $context): FormEntity
    {
        if (!$action) {
            throw new \Exception("Missing action parameter in Twig snippet");
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('action', $action));
        $criteria->addFilter(new EqualsFilter('type','snippet'));
        $criteria->addFilter(new EqualsFilter('active','1'));

        $this->formService->initForms($context, $criteria);
        $this->formService->setCheckCache(true);
        $this->formService->initCurrentFormByAction($action);
        $this->formService->setCheckCache(false);

        $form = $this->formService->getCurrentForm();

        if (!$form) {
            throw new \Exception("No active snippet form found for action: " . $action);
        }

        return $form;
    }
}
