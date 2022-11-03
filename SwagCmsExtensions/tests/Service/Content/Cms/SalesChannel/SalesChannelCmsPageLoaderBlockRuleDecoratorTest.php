<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Test\Service\Content\Cms\SalesChannel;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Rule\Container\OrRule;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Currency\Rule\CurrencyRule;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Swag\CmsExtensions\Service\Content\Cms\SalesChannel\SalesChannelCmsPageLoaderBlockRuleDecorator;
use Symfony\Component\HttpFoundation\Request;

class SalesChannelCmsPageLoaderBlockRuleDecoratorTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @var SalesChannelCmsPageLoaderBlockRuleDecorator
     */
    private $decorator;

    /**
     * @var AbstractSalesChannelContextFactory
     */
    private $salesChannelContextFactory;

    /**
     * @var EntityRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $ruleRepository;

    /**
     * @var string
     */
    private $cmsPageId;

    public function setUp(): void
    {
        parent::setUp();
        $container = $this->getContainer();

        $this->decorator = $container->get(SalesChannelCmsPageLoaderBlockRuleDecorator::class);

        $this->salesChannelContextFactory = $container->get(SalesChannelContextFactory::class);
        $this->categoryRepository = $container->get('category.repository');
        $this->ruleRepository = $container->get('rule.repository');
    }

    public function testAddBlockRuleAssociationAddsCorrectAssociation(): void
    {
        $criteria = $this->getMockBuilder(Criteria::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addAssociation', 'getAssociation'])
            ->getMock();

        $criteria->expects(static::once())
            ->method('addAssociation')
            ->with(SalesChannelCmsPageLoaderBlockRuleDecorator::BLOCK_RULE_VISIBILITY_RULE_ASSOCIATION_PATH);

        $this->decorator->load(
            new Request(),
            $criteria,
            $this->salesChannelContextFactory->create(Uuid::randomHex(), Defaults::SALES_CHANNEL)
        );
    }

    public function testLoadWithoutAnySections(): void
    {
        $salesChannelContext = $this->salesChannelContextFactory->create(Uuid::randomHex(), Defaults::SALES_CHANNEL);
        $categoryData = $this->getCategoryData();
        $categoryData['cmsPage']['sections'] = null;

        $this->categoryRepository->create(
            [$categoryData],
            $salesChannelContext->getContext()
        );

        $criteria = new Criteria([$this->cmsPageId]);

        $page = $this->decorator->load(
            new Request(),
            $criteria,
            $salesChannelContext
        )->get($this->cmsPageId);

        static::assertCount(0, $page->getSections());
    }

    public function testLoadWithoutAnyBlocks(): void
    {
        $salesChannelContext = $this->salesChannelContextFactory->create(Uuid::randomHex(), Defaults::SALES_CHANNEL);
        $categoryData = $this->getCategoryData();
        $categoryData['cmsPage']['sections'][0]['blocks'] = null;

        $this->categoryRepository->create(
            [$categoryData],
            $salesChannelContext->getContext()
        );

        $criteria = new Criteria([$this->cmsPageId]);

        $page = $this->decorator->load(
            new Request(),
            $criteria,
            $salesChannelContext
        )->get($this->cmsPageId);

        static::assertCount(0, $page->getSections()->getBlocks());
        static::assertCount(0, $page->getSections());
    }

    public function testLoadBlocksWithNoRulesMatching(): void
    {
        $page = $this->mockDecoratorLoad(false)->get($this->cmsPageId);
        static::assertCount(1, $page->getSections()->getBlocks());
    }

    public function testLoadBlocksWithOneRuleMatching(): void
    {
        $page = $this->mockDecoratorLoad(true)->get($this->cmsPageId);
        static::assertCount(2, $page->getSections()->getBlocks());
    }

    private function mockDecoratorLoad(bool $hasMatchingRulesInSalesChannel): EntitySearchResult
    {
        $salesChannelContext = $this->salesChannelContextFactory->create(Uuid::randomHex(), Defaults::SALES_CHANNEL);
        $matchingRuleId = $this->createMockRule($salesChannelContext->getContext());
        $notMatchingRuleId = $this->createMockRule($salesChannelContext->getContext());

        $this->categoryRepository->create(
            [$this->getCategoryData([$matchingRuleId, $notMatchingRuleId])],
            $salesChannelContext->getContext()
        );

        $criteria = new Criteria([$this->cmsPageId]);

        if ($hasMatchingRulesInSalesChannel) {
            $salesChannelContext->setRuleIds([$matchingRuleId]);
        }

        return $this->decorator->load(
            new Request(),
            $criteria,
            $salesChannelContext
        );
    }

    private function createMockRule(Context $context): string
    {
        $ruleId = Uuid::randomHex();

        $data = [
            'id' => $ruleId,
            'name' => 'test rule',
            'priority' => 1,
            'conditions' => [
                [
                    'type' => (new OrRule())->getName(),
                    'children' => [
                        [
                            'type' => (new CurrencyRule())->getName(),
                            'value' => [
                                'currencyIds' => [
                                    Uuid::randomHex(),
                                    Uuid::randomHex(),
                                ],
                                'operator' => CurrencyRule::OPERATOR_EQ,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->ruleRepository->create([$data], $context);

        return $ruleId;
    }

    private function getCategoryData(array $ruleIds = []): array
    {
        $this->cmsPageId = Uuid::randomHex();

        $blocks = [
            [
                'type' => 'text',
                'position' => 0,
                'slots' => [
                    [
                        'id' => Uuid::randomHex(),
                        'type' => 'text',
                        'slot' => 'content',
                        'config' => null,
                    ],
                ],
            ],
        ];

        foreach ($ruleIds as $index => $ruleId) {
            $blocks[] = [
                'type' => 'text',
                'position' => $index,
                'swagCmsExtensionsBlockRule' => [
                    'id' => Uuid::randomHex(),
                    // this is an issue with the test database
                    'cmsBlockVersionId' => Defaults::LIVE_VERSION,
                    'inverted' => false,
                    'visibilityRuleId' => $ruleId,
                ],
                'slots' => [
                    [
                        'id' => Uuid::randomHex(),
                        'type' => 'text',
                        'slot' => 'content',
                        'config' => null,
                    ],
                ],
            ];
        }

        return [
            'id' => Uuid::randomHex(),
            'name' => 'test category',
            'cmsPage' => [
                'id' => $this->cmsPageId,
                'name' => 'test page',
                'type' => 'landingpage',
                'sections' => [
                    [
                        'id' => Uuid::randomHex(),
                        'type' => 'default',
                        'position' => 0,
                        'blocks' => $blocks,
                    ],
                ],
            ],
        ];
    }
}
