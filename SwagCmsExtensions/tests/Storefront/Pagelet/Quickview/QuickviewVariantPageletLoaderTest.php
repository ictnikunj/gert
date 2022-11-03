<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Test\Storefront\Pagelet\Quickview;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\Exception\ProductNotFoundException;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Storefront\Test\Page\StorefrontPageTestBehaviour;
use Swag\CmsExtensions\Storefront\Pagelet\Quickview\QuickviewPageletLoaderInterface;
use Swag\CmsExtensions\Storefront\Pagelet\Quickview\QuickviewVariantPageletLoader;
use Swag\CmsExtensions\Test\Helper\QuickviewHelperTrait;
use Symfony\Component\HttpFoundation\Request;

class QuickviewVariantPageletLoaderTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StorefrontPageTestBehaviour;
    use QuickviewHelperTrait;

    /**
     * @var QuickviewPageletLoaderInterface
     */
    private $quickviewVariantPageletLoader;

    public function setUp(): void
    {
        $this->quickviewVariantPageletLoader = $this->getContainer()->get(QuickviewVariantPageletLoader::class);
    }

    public function testItRequiresAnExistingCombinationWithNoProduct(): void
    {
        $context = $this->createSalesChannelContext();
        $product = $this->getRandomProduct($context);

        $groupId = Uuid::randomHex();
        $request = new Request([
            'options' => $this->getRandomOptions($groupId),
            'switched' => $groupId,
            'parentId' => $product->getId(),
        ], [], [
            'productId' => $product->getId(),
        ]);

        $this->expectException(ProductNotFoundException::class);
        $this->getPageLoader()->load($request, $context);
    }

    public function testItHasAnExistingCombinationAndFoundAProduct(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();
        $this->createProduct($salesChannelContext);
        $options = $this->getVariantOptions();
        $request = $this->getVariantRequest($options, $salesChannelContext);

        $result = $this->getPageLoader()->load($request, $salesChannelContext);
        static::assertNotNull($result);
    }

    protected function getPageLoader(): QuickviewPageletLoaderInterface
    {
        return $this->quickviewVariantPageletLoader;
    }
}
