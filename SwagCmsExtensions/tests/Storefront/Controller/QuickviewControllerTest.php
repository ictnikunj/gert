<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Test\Storefront\Controller;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Framework\Routing\RequestTransformer;
use Shopware\Storefront\Test\Page\StorefrontPageTestBehaviour;
use Swag\CmsExtensions\Storefront\Controller\QuickviewController;
use Swag\CmsExtensions\Storefront\Pagelet\Quickview\QuickviewPageletLoader;
use Swag\CmsExtensions\Storefront\Pagelet\Quickview\QuickviewPageletLoaderInterface;
use Swag\CmsExtensions\Storefront\Pagelet\Quickview\QuickviewVariantPageletLoader;
use Swag\CmsExtensions\Test\Helper\QuickviewHelperTrait;
use Symfony\Component\HttpFoundation\Request;

class QuickviewControllerTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StorefrontPageTestBehaviour;
    use QuickviewHelperTrait;

    /**
     * @var QuickviewController
     */
    private $quickviewController;

    /**
     * @var SalesChannelContext
     */
    private $salesChannelContext;

    public function setUp(): void
    {
        $this->quickviewController = $this->getContainer()->get(QuickviewController::class);
        $this->salesChannelContext = $this->createSalesChannelContext();
    }

    public function testQuickviewRendersCorrectly(): void
    {
        $randomProduct = $this->getRandomProduct($this->salesChannelContext);
        $request = $this->getQuickviewRequest($randomProduct);
        $this->getContainer()->get('request_stack')->push($request);

        $result = $this->quickviewController->quickview($this->salesChannelContext, $request);
        static::assertNotNull($result);
        static::assertEquals(200, $result->getStatusCode());
    }

    public function testQuickviewRendersVariantCorrectly(): void
    {
        $this->createProduct($this->salesChannelContext);
        $options = $this->getVariantOptions();

        $request = $this->getVariantRequest($options, $this->salesChannelContext);
        $this->getContainer()->get('request_stack')->push($request);

        $result = $this->quickviewController->quickviewVariant($this->salesChannelContext, $request);
        static::assertNotNull($result);
        static::assertEquals(200, $result->getStatusCode());
    }

    protected function getPageLoader(): QuickviewPageletLoaderInterface
    {
        return $this->getContainer()->get(QuickviewPageletLoader::class);
    }

    protected function getVariantPageLoader(): QuickviewVariantPageletLoader
    {
        return $this->getContainer()->get(QuickviewVariantPageletLoader::class);
    }

    private function getQuickviewRequest(ProductEntity $product): Request
    {
        $request = new Request([], [], ['productId' => $product->getId()]);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT, $this->salesChannelContext);
        $request->attributes->set(RequestTransformer::STOREFRONT_URL, '');

        return $request;
    }
}
