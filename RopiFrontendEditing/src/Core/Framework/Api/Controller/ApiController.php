<?php declare(strict_types=1);

namespace Ropi\FrontendEditing\Core\Framework\Api\Controller;

use League\OAuth2\Server\Exception\OAuthServerException;
use Ropi\ContentEditor\Environment\Exception\RequestBodyParseException;
use Ropi\ContentEditor\Facade\ContentEditorActionFacade;
use Ropi\ContentEditor\Facade\ContentPresetActionFacade;
use Ropi\ContentEditor\Facade\Exception\ExportException;
use Ropi\ContentEditor\Facade\Exception\ImportException;
use Ropi\ContentEditor\Facade\Exception\RevertException;
use Ropi\ContentEditor\Service\Exception\InvalidDocumentContextInStructureException;
use Ropi\ContentEditor\Storage\Exception\VersionNotFoundException;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Api\Context\Exception\InvalidContextSourceException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\User\UserEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 */
class ApiController extends AbstractController
{

    /**
     * @var ContentEditorActionFacade
     */
    protected $contentEditorActionFacade;

    /**
     * @var ContentPresetActionFacade
     */
    protected $contentPresetActionFacade;

    /**
     * @var EntityRepositoryInterface
     */
    private $userRepository;

    public function __construct(
        ContentEditorActionFacade $contentEditorActionFacade,
        ContentPresetActionFacade $contentPresetActionFacade,
        EntityRepositoryInterface $userRepository
    ) {
        $this->contentEditorActionFacade = $contentEditorActionFacade;
        $this->contentPresetActionFacade = $contentPresetActionFacade;
        $this->userRepository = $userRepository;
    }

    /**
     * @Route(
     *     "/api/v{version}/_action/ropi/frontend-editing/document/save",
     *     name="api.ropi.frontend-editing.document.save",
     *     methods={"POST"},
     *     requirements={"version"="\d+"}
     * )
     * @throws InvalidContextSourceException
     * @throws OAuthServerException
     * @throws RequestBodyParseException
     * @throws InconsistentCriteriaIdsException
     */
    public function saveDocument(Context $context): Response
    {
        $this->contentEditorActionFacade->save(
            $this->fetchCurrentUser($context)->getUsername()
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route(
     *     "/api/v{version}/_action/ropi/frontend-editing/document/unpublish",
     *     name="api.ropi.frontend-editing.document.unpublish",
     *     methods={"POST"},
     *     requirements={"version"="\d+"}
     * )
     * @throws RequestBodyParseException
     * @throws InvalidDocumentContextInStructureException
     */
    public function unpublishDocument(): Response
    {
        $this->contentEditorActionFacade->unpublish();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route(
     *     "/api/v{version}/_action/ropi/frontend-editing/document/revert",
     *     name="api.ropi.frontend-editing.document.revert",
     *     methods={"POST"},
     *     requirements={"version"="\d+"}
     * )
     * @throws InvalidContextSourceException
     * @throws OAuthServerException
     * @throws RequestBodyParseException
     * @throws RevertException
     * @throws InvalidDocumentContextInStructureException
     * @throws VersionNotFoundException
     * @throws InconsistentCriteriaIdsException
     */
    public function revertDocument(Context $context): Response
    {
        $this->contentEditorActionFacade->revert(
            $this->fetchCurrentUser($context)->getUsername()
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route(
     *     "/api/v{version}/_action/ropi/frontend-editing/document/export-to-sales-channels",
     *     name="api.ropi.frontend-editing.document.export-to-sales-channels",
     *     methods={"POST"},
     *     requirements={"version"="\d+"}
     * )
     * @param Context $context
     * @return Response
     * @throws InvalidContextSourceException
     * @throws OAuthServerException
     * @throws RequestBodyParseException
     * @throws ExportException
     * @throws InvalidDocumentContextInStructureException
     * @throws InconsistentCriteriaIdsException
     */
    public function exportToSalesChannels(Context $context): Response
    {
        $this->contentEditorActionFacade->exportToSalesChannels(
            $this->fetchCurrentUser($context)->getUsername()
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route(
     *     "/api/v{version}/_action/ropi/frontend-editing/document/import",
     *     name="api.ropi.frontend-editing.document.import",
     *     methods={"POST"},
     *     requirements={"version"="\d+"}
     * )
     * @param Context $context
     * @return Response
     * @throws InvalidContextSourceException
     * @throws OAuthServerException
     * @throws RequestBodyParseException
     * @throws ImportException
     * @throws InvalidDocumentContextInStructureException
     * @throws InconsistentCriteriaIdsException
     */
    public function import(Context $context): Response
    {
        $this->contentEditorActionFacade->import(
            $this->fetchCurrentUser($context)->getUsername()
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route(
     *     "/api/v{version}/_action/ropi/frontend-editing/document/import-from-document-context",
     *     name="api.ropi.frontend-editing.document.import-from-document-context",
     *     methods={"POST"},
     *     requirements={"version"="\d+"}
     * )
     * @param Context $context
     * @return Response
     * @throws InvalidContextSourceException
     * @throws OAuthServerException
     * @throws RequestBodyParseException
     * @throws ImportException
     * @throws InvalidDocumentContextInStructureException
     * @throws InconsistentCriteriaIdsException
     */
    public function importFromDocumentContext(Context $context): Response
    {
        $this->contentEditorActionFacade->importFromDocumentContext(
            $this->fetchCurrentUser($context)->getUsername()
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route(
     *     "/api/v{version}/_action/ropi/frontend-editing/preset/{name}",
     *     name="api.ropi.frontend-editing.preset.save",
     *     methods={"POST", "DELETE"},
     *     requirements={"version"="\d+", "name"=".*"}
     * )
     * @throws InvalidContextSourceException
     * @throws RequestBodyParseException
     * @throws InconsistentCriteriaIdsException
     */
    public function savePreset(Request $request, string $name): Response
    {
        if ($request->getMethod() === Request::METHOD_DELETE) {
            $this->contentPresetActionFacade->delete($name);
        } else {
            $this->contentPresetActionFacade->save($name);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @throws InvalidContextSourceException
     * @throws OAuthServerException
     * @throws InconsistentCriteriaIdsException
     */
    private function fetchCurrentUser(Context $context): UserEntity
    {
        if (!$context->getSource() instanceof AdminApiSource) {
            throw new InvalidContextSourceException(AdminApiSource::class, get_class($context->getSource()));
        }

        $userId = $context->getSource()->getUserId();

        /** @var UserEntity|null $user */
        $user = $this->userRepository->search(new Criteria([$userId]), $context)->first();
        if (!$user) {
            throw OAuthServerException::invalidCredentials();
        }

        return $user;
    }
}