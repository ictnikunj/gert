<?php declare(strict_types=1);

namespace Ropi\FrontendEditing\Storefront\Controller;

use Ropi\ContentEditor\Facade\ContentPresetActionFacade;
use Ropi\ContentEditor\Storage\ContentPresetStorageInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Adapter\Translation\Translator;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\User\UserEntity;
use Symfony\Component\HttpFoundation\Cookie;
use Ropi\ContentEditor\Environment\ContentEditorEnvironmentInterface;
use Ropi\ContentEditor\Renderer\ContentElementRendererInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainCollection;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Storefront\Framework\Routing\StorefrontResponse;
use Symfony\Component\HttpFoundation\Response;
use Ropi\FrontendEditing\ContentEditor\Environment\ContentEditorEnvironment;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpFoundation\Request;

class FrontendEditingController extends StorefrontController
{

    /**
     * @var ContentEditorEnvironment
     */
    protected $contentEditorEnvironment;

    /**
     * @var ContentElementRendererInterface
     */
    protected $contentElementRenderer;

    /**
     * @var ContentPresetStorageInterface
     */
    protected $contentPresetStorage;

    public function __construct(
        ContentEditorEnvironmentInterface $contentEditorEnvironment,
        ContentElementRendererInterface $contentElementRenderer,
        ContentPresetStorageInterface $contentPresetStorage
    ) {
        $this->contentEditorEnvironment = $contentEditorEnvironment;
        $this->contentElementRenderer = $contentElementRenderer;
        $this->contentPresetStorage = $contentPresetStorage;
    }

    /**
     * @RouteScope(scopes={"administration", "storefront"})
     * @Route(
     *     "/frontend-editing",
     *     name="ropi.frontend-editing",
     *     defaults={"auth_required"=false, "ropi_frontend_editing_content_editor"=true},
     *     options={"seo"="false"},
     *     methods={"GET"}
     * )
     */
    public function index(Request $request): Response
    {
        $response = $this->renderStorefront("@RopiFrontendEditing/content-editor/index.html.twig", [
            'editorUrl' => $request->getUriForPath('/frontend-editing/content-editor'),
            'administrationUrl' => $request->getUriForPath('/admin'),
            'loginUrl' => $request->getUriForPath('/api/oauth/token')
        ]);

        return $response;
    }

    /**
     * @RouteScope(scopes={"administration", "storefront"})
     * @Route(
     *     "/frontend-editing/content-editor",
     *     name="ropi.frontend-editing.content-editor",
     *     defaults={"ropi_frontend_editing_content_editor"=true},
     *     options={"seo"="false"},
     *     methods={"GET"}
     * )
     * @throws \Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException
     * @throws \Exception
     */
    public function contentEditor(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        $userId = $this->getCurrentUserIdFromRequest($request);
        $token = $this->generateToken();

        $this->updateUserToken($userId, $token, $salesChannelContext->getContext());

        $user = $this->fetchUser($userId, $salesChannelContext->getContext());

        $this->initUserLanguage($user, $salesChannelContext);

        $response = $this->renderStorefront('@RopiFrontendEditing/content-editor/content-editor.html.twig', [
            'src' => $request->getUriForPath('/'),
            'breakpoints' => $this->contentEditorEnvironment->getBreakpoints(),
            'sites' => $this->resolveSites($salesChannelContext->getContext()),
            'user' => $user,
            'contentEditorSnippets' => $this->getContentEditorSnippets($user),
            'presets' => $this->contentPresetStorage->loadAll()
        ]);

        $response->headers->setCookie(
            Cookie::create(
                'ropi-frontend-editing-token',
                $token,
                0,
                '/',
                null,
                null,
                false
            )
        );

        return $response;
    }

    /**
     * @RouteScope(scopes={"administration", "storefront"})
     * @Route(
     *     "/frontend-editing/element/render",
     *     name="ropi.frontend-editing.element.render",
     *     methods={"POST"},
     *     options={"seo"="false"},
     *     defaults={"csrf_protected"=false}
     * )
     */
    public function renderElement(Request $request): Response
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)
            || !isset($payload['data'])
            || !is_array($payload['data'])
            || !isset($payload['data']['meta']['type'])
            || !is_string($payload['data']['meta']['type']))
        {
            throw new BadRequestHttpException('Invalid or missing payload');
        }

        $content = $this->contentElementRenderer->render($payload['data']['meta']['type'], $payload['data']);

        $response = new StorefrontResponse();
        $response->headers->set('Content-Type', 'text/html');
        $response->setContent($content);

        return $response;
    }

    /**
     * @throws \Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException
     */
    private function resolveSites(Context $context): array
    {
        /** @var EntityRepositoryInterface $salesChannelRepository */
        $salesChannelRepository = $this->get('sales_channel.repository');

        /** @var SalesChannelCollection $salesChannelCollection */
        $salesChannelCollection = $salesChannelRepository->search(
            (new Criteria())
                ->addFilter(new EqualsFilter('typeId', Defaults::SALES_CHANNEL_TYPE_STOREFRONT))
                ->addAssociation('domains.language'),
            $context
        )->getEntities();

        $sites = [];

        foreach ($salesChannelCollection->getElements() as $salesChannel) {
            /** @var SalesChannelDomainCollection $domainCollection */
            $domainCollection = $salesChannel->getDomains();
            if (!$domainCollection instanceof SalesChannelDomainCollection) {
                continue;
            }

            foreach ($domainCollection->getElements() as $domain) {
                $language = $domain->getLanguage();
                if (!$language) {
                    continue;
                }

                $key = $salesChannel->getId() . '/' . $domain->getLanguageId();
                if (isset($sites[$key])) {
                    continue;
                }

                $sites[$key] = [
                    'documentContextDelta' => [
                        'salesChannelId' => $salesChannel->getId(),
                        'languageId' => $language->getId()
                    ],
                    'name' => $salesChannel->getName() . " ({$language->getName()})",
                    'editorUrl' => rtrim($domain->getUrl(), '/') . '/frontend-editing'
                ];
            }
        }

        return array_values($sites);
    }

    /**
     * @throws \Exception
     */
    private function updateUserToken(string $userId, string $token, Context $context)
    {
        /** @var EntityRepositoryInterface $userRepository */
        $userRepository = $this->get('user.repository');

        $userRepository->update(
            [
                [
                    'id' => $userId,
                    'customFields' => [
                        'ropi_frontend_editing_token' => $token
                    ]
                ]
            ],
            Context::createDefaultContext()
        );
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function generateToken()
    {
        return bin2hex(random_bytes(32));
    }

    private function getCurrentUserIdFromRequest(Request $request): string
    {
        $userId = $request->attributes->get(PlatformRequest::ATTRIBUTE_OAUTH_USER_ID);
        if (!$userId) {
            throw new \RuntimeException(
                'Failed to get Oauth user ID from request',
                1592771479
            );
        }

        return $userId;
    }

    /**
     * @throws \Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException
     */
    private function fetchUser(string $userId, Context $context): UserEntity
    {
        /** @var EntityRepositoryInterface $userRepository */
        $userRepository = $this->get('user.repository');

        $criteria = (new Criteria([$userId]))->addAssociation('locale.languages');

        $userEntity = $userRepository->search($criteria, $context)->getEntities()->get($userId);
        if (!$userEntity instanceof UserEntity) {
            throw new \RuntimeException(
                'Authenticated user not found in database',
                1592777868
            );
        }

        return $userEntity;
    }

    private function initUserLanguage(UserEntity $user, SalesChannelContext $salesChannelContext): void
    {
        /** @var Translator $translator */
        $translator = $this->get('translator');
        $translator->injectSettings(
            $salesChannelContext->getSalesChannel()->getId(),
            $user->getLocale()->getLanguages()->first()->getId(),
            $user->getLocale()->getCode(),
            $salesChannelContext->getContext()
        );
    }

    private function getContentEditorSnippets(UserEntity $user): array
    {
        $contentEditorSnippets = [];

        /** @var Translator $translator */
        $translator = $this->get('translator');

        $messages = $translator->getCatalogue($user->getLocale()->getCode())->all()['messages'] ?? [];
        foreach ($messages as $key => $message) {
            if (strpos($key, 'ropi-frontend-editing.contentEditor.') === 0) {
                $shortKey = preg_replace('/ropi-frontend-editing\\.contentEditor\\./', '', $key, 1);

                $keySegments = explode('.', $shortKey);

                $currentSegment =& $contentEditorSnippets;

                foreach ($keySegments as $keySegment) {
                    if (isset($currentSegment) && !is_array($currentSegment)) {
                        $currentSegment = [];
                    }

                    $currentSegment =& $currentSegment[$keySegment];
                }

                $currentSegment = $message;
            }
        }

        return $contentEditorSnippets;
    }
}