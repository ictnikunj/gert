<?php declare(strict_types=1);

namespace Stutt\ArticleUrl\Service;

use Symfony\Component\Routing\RouterInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Content\Product\ProductEntity;

class ServiceDecorator implements RouterInterface, WarmableInterface
{
    /**
     * The original service which could be used in the decorator
     *
     * @var \Shopware\Storefront\Framework\Routing\Router
     */
    private $decoratedService;
    /**
     * @var SystemConfigService
     */
    private $systemConfigService;
    /**
     * The original service which could be used in the decorator
     *
     * @var \Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface
     */
    private $productRepository;

    public function __construct(
        RouterInterface $myService,
        SystemConfigService $systemConfigService,
        \Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface $productRepository
    )
    {
        $this->decoratedService = $myService;
        $this->systemConfigService = $systemConfigService;
        $this->productRepository = $productRepository;
    }

    public function warmUp($cacheDir) {
        // Nothing to do
    }

    /**
     * Sets the request context.
     */
    public function setContext(\Symfony\Component\Routing\RequestContext $context)
    {
        return $this->decoratedService->setContext($context);
    }

    /**
     * Gets the request context.
     *
     * @return \Symfony\Component\Routing\RequestContext The context
     */
    public function getContext()
    {
        return $this->decoratedService->getContext();
    }

    /**
     * Gets the RouteCollection instance associated with this Router.
     *
     * @return \Symfony\Component\Routing\RouteCollection A RouteCollection instance
     */
    public function getRouteCollection()
    {
        return $this->decoratedService->getRouteCollection();
    }

    /**
     * Generates a URL or path for a specific route based on the given parameters.
     *
     * Parameters that reference placeholders in the route pattern will substitute them in the
     * path or host. Extra params are added as query string to the URL.
     *
     * When the passed reference type cannot be generated for the route because it requires a different
     * host or scheme than the current one, the method will return a more comprehensive reference
     * that includes the required params. For example, when you call this method with $referenceType = ABSOLUTE_PATH
     * but the route requires the https scheme whereas the current scheme is http, it will instead return an
     * ABSOLUTE_URL with the https scheme and the current host. This makes sure the generated URL matches
     * the route in any case.
     *
     * If there is no route with the given name, the generator must throw the RouteNotFoundException.
     *
     * The special parameter _fragment will be used as the document fragment suffixed to the final URL.
     *
     * @param string $name The name of the route
     * @param mixed[] $parameters An array of parameters
     * @param int $referenceType The type of reference to be generated (one of the constants)
     *
     * @return string The generated URL
     *
     * @throws \Symfony\Component\Routing\Exception\RouteNotFoundException              If the named route doesn't exist
     * @throws \Symfony\Component\Routing\Exception\MissingMandatoryParametersException When some parameters are missing that are mandatory for the route
     * @throws \Symfony\Component\Routing\Exception\InvalidParameterException           When a parameter value for a placeholder is not correct because
     *                                             it does not match the requirement
     */
    public function generate($name, $parameters = [], $referenceType = RouterInterface::ABSOLUTE_PATH)
    {
        return $this->decoratedService->generate($name, $parameters, $referenceType);
    }

    /**
     * Tries to match a URL path with a set of routes.
     *
     * If the matcher can not find information, it must throw one of the exceptions documented
     * below.
     *
     * @param string $pathinfo The path info to be parsed (raw format, i.e. not urldecoded)
     *
     * @return array An array of parameters
     *
     * @throws \Symfony\Component\Routing\Exception\NoConfigurationException  If no routing configuration could be found
     * @throws \Symfony\Component\Routing\Exception\ResourceNotFoundException If the resource could not be found
     * @throws \Symfony\Component\Routing\Exception\MethodNotAllowedException If the resource was found but the request method is not allowed
     */
    public function match($pathinfo)
    {
        $subpath = $this->systemConfigService->get('StuttArticleUrl.config.subpath');
        if (strlen((string) $subpath) > 0) {
            $subpath = '/' . trim($subpath,  '/') . '/';

            if (substr($pathinfo, 0, strlen($subpath)) === $subpath) {
                /** @var \Shopware\Core\Framework\DataAbstractionLayer\EntityCollection $entities */
                $products = $this->productRepository->search(
                    (new Criteria())->addFilter(new EqualsFilter('productNumber', urldecode(substr($pathinfo, strlen($subpath))))),
                    \Shopware\Core\Framework\Context::createDefaultContext()
                );
                if ($products->count() > 0) {
                    /** @var ProductEntity $product */
                    $product = $products->first();

                    if ($product->getParentId() !== NULL) {
                        $parentProductSearch = $this->productRepository->search(new Criteria([$product->getParentId()]), \Shopware\Core\Framework\Context::createDefaultContext());
                        if ($parentProductSearch->count() > 0) {
                            /** @var ProductEntity $parentProduct */
                            $parentProduct = $parentProductSearch->first();
                            $productOrParentActive = (bool) $parentProduct->getActive();
                        }
                        else {
                            $productOrParentActive = FALSE;
                        }
                    }
                    else {
                        $productOrParentActive = (bool) $product->getActive();
                    }

                    if ($productOrParentActive) {
                        return [
                            '_routeScope'  => ['storefront'],
                            '_route' => 'frontend.detail.page',
                            '_controller' => 'Shopware\Storefront\Controller\ProductController::index',
                            'productId' => $product->getId(),
                        ];
                    }
                }
            }
        }

        return $this->decoratedService->match($pathinfo);
    }
}