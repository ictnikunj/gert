<?php declare(strict_types=1);

namespace MoorlFormBuilder\Controller;

use MoorlFormBuilder\Core\Content\Form\FormEntity;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

abstract class MoorlFormBuilderStorefrontController extends StorefrontController
{
    public function addViolationsFlash(array $violations): void
    {
        if ($violations) {
            foreach ($violations as $violation) {
                $this->addFlash('danger', $this->trans($violation['snippet'], $violation['data']));
            }
        }
    }

    public function getJsonViolationResponse(array $violations): JsonResponse
    {
        foreach ($violations as $violation) {
            $translated[] = $this->trans($violation['snippet'], $violation['data']);
        }

        return new JsonResponse([
            [
                'type' => 'danger',
                'alert' => $this->renderView('@Storefront/storefront/utilities/alert.html.twig', [
                    'type' => 'danger',
                    'list' => $translated,
                ]),
            ]
        ]);
    }

    public function getJsonSuccessResponse(FormEntity $form, ?string $localeCode = null, $reload = null, $redirectTo = null): JsonResponse
    {
        $redirectTo = null;

        if ($form->getRedirectTo()) {
            $canRedirect = true;

            if (!empty($form->getRedirectConditions()) && is_array($form->getRedirectConditions())) {
                foreach ($form->getRedirectConditions() as $condition) {
                    if (empty($form->getDataValue($condition['name']))) {
                        $canRedirect = false;
                    }
                }
            }

            if ($canRedirect) {
                if (substr($form->getRedirectTo(), 0, 4) === 'http') {
                    $redirectTo = $form->getRedirectTo();
                } else if (substr($form->getRedirectTo(), 0, 1) === '/') {
                    $redirectTo = $form->getRedirectTo();
                } else {
                    $redirectParams = [];
                    $userValues = $form->getUserValues();

                    if ($form->getRedirectParams()) {
                        $rawRedirectParams = $form->getRedirectParams();
                        $rawRedirectParams = explode(';', $rawRedirectParams);

                        foreach ($rawRedirectParams as $rawRedirectParam) {
                            $rawRedirectParam = explode(':', $rawRedirectParam);
                            $redirectParams[$rawRedirectParam[0]] = isset($userValues[$rawRedirectParam[1]]) ? $userValues[$rawRedirectParam[1]] : $rawRedirectParam[1];
                        }
                    }

                    $redirectTo = $redirectTo . $this->generateUrl($form->getRedirectTo(), $redirectParams);
                }
            }
        }

        return new JsonResponse([
            [
                'reload' => $redirectTo ? null : $reload,
                'redirectTo' => $redirectTo,
                'type' => 'success',
                'alert' => $this->renderView('@Storefront/storefront/utilities/alert.html.twig', [
                    'type' => 'success',
                    'list' => [
                        $this->trans(isset($form->getSuccessMessage()[$localeCode]) ? $form->getSuccessMessage()[$localeCode] : 'moorl-form-builder.general.successMessage', $form->getUserValues())
                    ],
                ])
            ]
        ]);
    }
}
