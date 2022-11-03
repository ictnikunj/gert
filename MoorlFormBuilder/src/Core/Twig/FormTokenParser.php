<?php declare(strict_types=1);

namespace MoorlFormBuilder\Core\Twig;

use http\Exception\InvalidArgumentException;
use MoorlFormBuilder\Core\Content\Form\FormEntity;
use Shopware\Core\Framework\Adapter\Twig\Node\SwInclude;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Node;
use Twig\Parser;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

final class FormTokenParser extends AbstractTokenParser
{
    /**
     * @var Parser
     */
    protected $parser;

    public function parse(Token $token): SwInclude
    {
        $expr = $this->parser->getExpressionParser()->parseExpression();

        $action = $expr->getAttribute('value');

        $expr->setAttribute('value', '@MoorlFormBuilder/plugin/moorl-form-builder/component/form-snippet.html.twig');

        $stream = $this->parser->getStream();

        $variables = new ArrayExpression([], $token->getLine());

        if ($stream->nextIf(Token::NAME_TYPE, 'with')) {
            /** @var ArrayExpression $variables */
            $variables = $this->parser->getExpressionParser()->parseExpression();
        }

        $stream->next();

        $variables->addElement(
            new ConstantExpression($action, $token->getLine()),
            new ConstantExpression('name', $token->getLine())
        );

        return new SwInclude($expr, $variables, false, false, $token->getLine(), $this->getTag());
    }

    public function getTag(): string
    {
        return 'moorl_form';
    }
}