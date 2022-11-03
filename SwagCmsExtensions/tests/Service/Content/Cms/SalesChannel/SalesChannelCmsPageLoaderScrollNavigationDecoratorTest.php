<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Test\Service\Content\Cms\SalesChannel;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Cms\SalesChannel\SalesChannelCmsPageLoader;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Swag\CmsExtensions\Service\Content\Cms\SalesChannel\SalesChannelCmsPageLoaderScrollNavigationDecorator;
use Symfony\Component\HttpFoundation\Request;

class SalesChannelCmsPageLoaderScrollNavigationDecoratorTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @var AbstractSalesChannelContextFactory
     */
    private $salesChannelContextFactory;

    /**
     * @var SalesChannelCmsPageLoaderScrollNavigationDecorator
     */
    private $decorator;

    public function setUp(): void
    {
        parent::setUp();

        $this->salesChannelContextFactory = $this->getContainer()->get(SalesChannelContextFactory::class);
        $this->decorator = new SalesChannelCmsPageLoaderScrollNavigationDecorator(
            $this->getContainer()->get(SalesChannelCmsPageLoader::class)
        );
    }

    public function testAddScrollNavigationAssociationAddsCorrectAssociation(): void
    {
        $criteria = $this->getMockBuilder(Criteria::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addAssociation'])
            ->getMock();

        $criteria->expects(static::atMost(2))
            ->method('addAssociation')
            ->withConsecutive(
                [SalesChannelCmsPageLoaderScrollNavigationDecorator::SCROLL_NAVIGATION_PAGE_SETTINGS_PATH],
                [SalesChannelCmsPageLoaderScrollNavigationDecorator::SCROLL_NAVIGATION_ASSOCIATION_PATH]
            );

        $this->decorator->load(
            new Request(),
            $criteria,
            $this->salesChannelContextFactory->create(Uuid::randomHex(), Defaults::SALES_CHANNEL)
        );
    }
}
