<?php

namespace MediaRedirect\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"storefront"})
 */
class GjrMediaController extends StorefrontController
{
    /**
     * @var EntityRepositoryInterface
     */
    private $repository;
    /**
     * @var EntityRepositoryInterface
     */
    private $ictMediaRedirect;

    public function __construct(
        EntityRepositoryInterface $repository,
        EntityRepositoryInterface $ictMediaRedirect
    ) {
        $this->repository = $repository;
        $this->ictMediaRedirect = $ictMediaRedirect;
    }

    /**
     * @Route("/document{path}", name="document.redirect", methods={"GET"}, requirements={"path"=".+"})
     */
    public function mediaPathStatic($path): RedirectResponse
    {
        $mediaData =  $this->ictMediaRedirect->search(
            (new Criteria())->addFilter(new ContainsFilter('url', $path)),
            Context::createDefaultContext()
        )->first();
        $result = false;
        if ($mediaData) {
            $getMediaId = $mediaData->getmediaId();
            if ($getMediaId) {
                $result = $this->repository->search(
                    (new Criteria())->addFilter(new EqualsFilter('id', $getMediaId)),
                    Context::createDefaultContext()
                )->first();
            }
        }
        if ($result) {
            return $this->redirect($result->getUrl());
        }
        throw $this->createNotFoundException('File not found!');
    }

    /**
     * @Route("/Letöltések{path}", name="Letöltések.redirect", methods={"GET"}, requirements={"path"=".+"})
     */
    public function mediaPathStaticLetoltesek($path): RedirectResponse
    {
        $mediaData =  $this->ictMediaRedirect->search(
            (new Criteria())->addFilter(new ContainsFilter('url', $path)),
            Context::createDefaultContext()
        )->first();
        $result = false;
        if ($mediaData) {
            $getMediaId = $mediaData->getmediaId();
            if ($getMediaId) {
                $result = $this->repository->search(
                    (new Criteria())->addFilter(new EqualsFilter('id', $getMediaId)),
                    Context::createDefaultContext()
                )->first();
            }
        }
        if ($result) {
            return $this->redirect($result->getUrl());
        }
        throw $this->createNotFoundException('File not found!');
    }

    /**
     * @Route("/Downloads{path}", name="Downloads.redirect", methods={"GET"}, requirements={"path"=".+"})
     */
    public function mediaPathStaticDownloads($path): RedirectResponse
    {
        $mediaData =  $this->ictMediaRedirect->search(
            (new Criteria())->addFilter(new ContainsFilter('url', $path)),
            Context::createDefaultContext()
        )->first();
        $result = false;
        if ($mediaData) {
            $getMediaId = $mediaData->getmediaId();
            if ($getMediaId) {
                $result = $this->repository->search(
                    (new Criteria())->addFilter(new EqualsFilter('id', $getMediaId)),
                    Context::createDefaultContext()
                )->first();
            }
        }
        if ($result) {
            return $this->redirect($result->getUrl());
        }
        throw $this->createNotFoundException('File not found!');
    }

    /**
     * @Route("/Stahování{path}", name="Stahování.redirect", methods={"GET"}, requirements={"path"=".+"})
     */
    public function mediaPathStaticStahovani($path): RedirectResponse
    {
        $mediaData =  $this->ictMediaRedirect->search(
            (new Criteria())->addFilter(new ContainsFilter('url', $path)),
            Context::createDefaultContext()
        )->first();
        $result = false;
        if ($mediaData) {
            $getMediaId = $mediaData->getmediaId();
            if ($getMediaId) {
                $result = $this->repository->search(
                    (new Criteria())->addFilter(new EqualsFilter('id', $getMediaId)),
                    Context::createDefaultContext()
                )->first();
            }
        }
        if ($result) {
            return $this->redirect($result->getUrl());
        }
        throw $this->createNotFoundException('File not found!');
    }

    /**
     * @Route("/Descargas{path}", name="Descargas.redirect", methods={"GET"}, requirements={"path"=".+"})
     */
    public function mediaPathStaticDescargas($path): RedirectResponse
    {
        $mediaData =  $this->ictMediaRedirect->search(
            (new Criteria())->addFilter(new ContainsFilter('url', $path)),
            Context::createDefaultContext()
        )->first();
        $result = false;
        if ($mediaData) {
            $getMediaId = $mediaData->getmediaId();
            if ($getMediaId) {
                $result = $this->repository->search(
                    (new Criteria())->addFilter(new EqualsFilter('id', $getMediaId)),
                    Context::createDefaultContext()
                )->first();
            }
        }
        if ($result) {
            return $this->redirect($result->getUrl());
        }
        throw $this->createNotFoundException('File not found!');
    }

    /**
    * @Route("/Preuzimanja{path}", name="Preuzimanja.redirect", methods={"GET"}, requirements={"path"=".+"})
    */
    public function mediaPathStaticPreuzimanja($path): RedirectResponse
    {
        $mediaData =  $this->ictMediaRedirect->search(
            (new Criteria())->addFilter(new ContainsFilter('url', $path)),
            Context::createDefaultContext()
        )->first();
        $result = false;
        if ($mediaData) {
            $getMediaId = $mediaData->getmediaId();
            if ($getMediaId) {
                $result = $this->repository->search(
                    (new Criteria())->addFilter(new EqualsFilter('id', $getMediaId)),
                    Context::createDefaultContext()
                )->first();
            }
        }
        if ($result) {
            return $this->redirect($result->getUrl());
        }
        throw $this->createNotFoundException('File not found!');
    }


    /**
     * @Route("/Download{path}", name="Download.redirect", methods={"GET"}, requirements={"path"=".+"})
     */
    public function mediaPathStaticDownload($path): RedirectResponse
    {
        $mediaData =  $this->ictMediaRedirect->search(
            (new Criteria())->addFilter(new ContainsFilter('url', $path)),
            Context::createDefaultContext()
        )->first();
        $result = false;
        if ($mediaData) {
            $getMediaId = $mediaData->getmediaId();
            if ($getMediaId) {
                $result = $this->repository->search(
                    (new Criteria())->addFilter(new EqualsFilter('id', $getMediaId)),
                    Context::createDefaultContext()
                )->first();
            }
        }
        if ($result) {
            return $this->redirect($result->getUrl());
        }
        throw $this->createNotFoundException('File not found!');
    }

    /**
     * @Route("/Prenosi{path}", name="Prenosi.redirect", methods={"GET"}, requirements={"path"=".+"})
     */
    public function mediaPathStaticPrenosi($path): RedirectResponse
    {
        $mediaData =  $this->ictMediaRedirect->search(
            (new Criteria())->addFilter(new ContainsFilter('url', $path)),
            Context::createDefaultContext()
        )->first();
        $result = false;
        if ($mediaData) {
            $getMediaId = $mediaData->getmediaId();
            if ($getMediaId) {
                $result = $this->repository->search(
                    (new Criteria())->addFilter(new EqualsFilter('id', $getMediaId)),
                    Context::createDefaultContext()
                )->first();
            }
        }
        if ($result) {
            return $this->redirect($result->getUrl());
        }
        throw $this->createNotFoundException('File not found!');
    }

    /**
     * @Route("/Pobierzpliki{path}", name="Pobierzpliki.redirect", methods={"GET"}, requirements={"path"=".+"})
     */
    public function mediaPathStaticPobierzpliki($path): RedirectResponse
    {
        $mediaData =  $this->ictMediaRedirect->search(
            (new Criteria())->addFilter(new ContainsFilter('url', $path)),
            Context::createDefaultContext()
        )->first();
        $result = false;
        if ($mediaData) {
            $getMediaId = $mediaData->getmediaId();
            if ($getMediaId) {
                $result = $this->repository->search(
                    (new Criteria())->addFilter(new EqualsFilter('id', $getMediaId)),
                    Context::createDefaultContext()
                )->first();
            }
        }
        if ($result) {
            return $this->redirect($result->getUrl());
        }
        throw $this->createNotFoundException('File not found!');
    }
}
