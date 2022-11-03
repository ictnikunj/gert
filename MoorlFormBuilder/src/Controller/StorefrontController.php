<?php

namespace MoorlFormBuilder\Controller;

use MoorlFormBuilder\Core\Captcha\MoorlCaptcha;
use MoorlFormBuilder\Core\Service\FormService;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"storefront"})
 */
class StorefrontController extends MoorlFormBuilderStorefrontController
{
    private SystemConfigService $systemConfigService;
    protected FormService $formService;

    public function __construct(
        SystemConfigService $systemConfigService,
        FormService $formService
    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->formService = $formService;
    }

    /**
     * @Route("/moorl-form-builder/fire/{moorlFormId}", name="moorl-form-builder.fire", options={"seo"="false"}, methods={"POST"}, defaults={"moorlFormId"=null,"XmlHttpRequest"=true})
     */
    public function fire(string $moorlFormId, SalesChannelContext $context): JsonResponse
    {
        $this->formService->setFormId($moorlFormId);
        $violations = $this->formService->fire($context->getContext(), $context);
        if ($violations) {
            return $this->getJsonViolationResponse($this->formService->getViolations());
        }

        $this->formService->fireForm();

        $customer = $context->getCustomer();

        $this->formService->addAppointment(
            $moorlFormId,
            [],
            [
                'customerId' => $customer ? $customer->getId() : null,
                'salesChannelId' => $context->getSalesChannelId()
            ]
        );

        $this->formService->unsetUserData();

        return $this->getJsonSuccessResponse(
            $this->formService->getCurrentForm(),
            $this->formService->getLocaleCode(),
            $this->formService->getPageReload()
        );
    }

    /**
     * @Route("/moorl-form-builder/update/{formId}", name="moorl-form-builder.update", options={"seo"="false"}, methods={"POST"}, defaults={"formId"=null,"XmlHttpRequest"=true})
     */
    public function update(string $formId, SalesChannelContext $context): JsonResponse
    {
        $this->formService->setFormId($formId);
        $violations = $this->formService->fire($context->getContext(), $context);

        if ($violations) {
            return $this->getJsonViolationResponse($this->formService->getViolations());
        }

        $this->formService->fireForm();

        return $this->getJsonSuccessResponse(
            $this->formService->getCurrentForm(),
            $this->formService->getLocaleCode(),
            true
        );
    }

    /**
     * @Route("/moorl-form-builder/remove/media/{mediaId}", name="moorl-form-builder.remove.media", methods={"GET"}, defaults={"mediaId"=null,"XmlHttpRequest"=true})
     */
    public function removeMedia(string $mediaId, SalesChannelContext $salesChannelContext): JsonResponse
    {
        $this->formService->setSalesChannelContext($salesChannelContext);
        $this->formService->removeUserMedia($mediaId);

        if ($this->formService->hasViolations()) {
            return $this->getJsonViolationResponse($this->formService->getViolations());
        }

        return new JsonResponse([
            [
                'removeId' => '#media-' . $mediaId
            ]
        ]);
    }

    /**
     * @Route("/moorl-form-builder/captcha/{id}.jpg", name="moorl-form-builder.captcha", methods={"GET"}, defaults={"id"=null})
     */
    public function captcha(string $id, Request $request): BinaryFileResponse
    {
        $session = $request->getSession();
        $captcha = MoorlCaptcha::generate($this->systemConfigService->getDomain('MoorlFormBuilder.config'));
        $session->set($id . MoorlCaptcha::CAPTCHA_SESSION, $captcha['captcha']);

        $tmpName = tempnam(sys_get_temp_dir(), 'img') . '.jpg';

        imagejpeg($captcha['im'], $tmpName);

        $response = (new BinaryFileResponse($tmpName))->deleteFileAfterSend(true);
        $response->headers->set('Cache-Control', 'no-cache, must-revalidate');
        $response->headers->set('Content-Type', 'image/jpeg');

        return $response;
    }

    /**
     * @Route("/moorl-form-builder/autocomplete/{formId}/{formElementId}.json", name="moorl-form-builder.autocomplete", methods={"GET"}, defaults={"XmlHttpRequest"=true})
     */
    public function autocomplete(string $formId, string $formElementId, SalesChannelContext $context): JsonResponse
    {
        $this->formService->setContext($context->getContext());
        $this->formService->setFormId($formId);

        return new JsonResponse($this->formService->autocomplete($formElementId, true));
    }
}
