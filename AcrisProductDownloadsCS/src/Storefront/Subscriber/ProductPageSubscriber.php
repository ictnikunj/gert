<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Storefront\Subscriber;

use Acris\ProductDownloads\Components\ProductDownloadService;
use Acris\ProductDownloads\Custom\ProductDownloadCollection;
use Shopware\Storefront\Page\Product\ProductPageCriteriaEvent;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\ArrayEntity;

class ProductPageSubscriber implements EventSubscriberInterface
{
    private ProductDownloadService $productDownloadService;
    private $mediaFolderRepository;
    private $mediaRepository;
    private $ictMediaRedirectRepository;

    public function __construct(
        ProductDownloadService $productDownloadService,
        EntityRepositoryInterface $mediaFolderRepository,
        EntityRepositoryInterface $mediaRepository,
        EntityRepositoryInterface $ictMediaRedirectRepository
    )
    {
        $this->productDownloadService = $productDownloadService;
        $this->mediaFolderRepository =  $mediaFolderRepository;
        $this->mediaRepository =  $mediaRepository;
        $this->ictMediaRedirectRepository =  $ictMediaRedirectRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductPageCriteriaEvent::class => 'onProductPageCriteriaLoaded',
            ProductPageLoadedEvent::class => 'onProductPageLoaded'
        ];
    }

    public function onProductPageCriteriaLoaded(ProductPageCriteriaEvent $event): void
    {

        //$this->productDownloadService->addProductAssociationCriteria($event->getCriteria());
        $event->getCriteria()->addAssociation('acrisDownloads');
        $event->getCriteria()->getAssociation('acrisDownloads')->addAssociation('media');
        $event->getCriteria()->getAssociation('acrisDownloads')->addSorting(new FieldSorting('position'))->addSorting(new FieldSorting('title', FieldSorting::ASCENDING, true))->addAssociation('languages');
    }

    public function onProductPageLoaded(ProductPageLoadedEvent $event):void
    {

        $languageId = $event->getContext()->getLanguageId();
        /** @var ProductDownloadCollection $acrisDownloads */
        if($event->getPage() && $event->getPage()->getProduct() && $acrisDownloads = $event->getPage()->getProduct()->getExtension('acrisDownloads')) {
            $mediaArray = array();
            foreach ($acrisDownloads->getElements() as $key => $productDownload) {
                $sub = array();
                $folderName = $this->mediaFolderRepository->search(
                    (new Criteria())->addFilter(new EqualsFilter('id', $productDownload->getMedia()->getMediaFolderId())),
                    $event->getContext())->first();
                $ictMediaRedirectRepository = $this->ictMediaRedirectRepository->search(
                    (new Criteria())->addFilter(new EqualsFilter('mediaId', $productDownload->getmediaId())),
                    $event->getContext())->first();

                $sub['id'] = $productDownload->getMedia()->getId();
                $sub['mediaUrl'] = $productDownload->getMedia()->getUrl();
                $sub['filename'] = $productDownload->getMedia()->getFileName();
                $sub['url'] = !empty($ictMediaRedirectRepository) ? $ictMediaRedirectRepository->geturl() : null;
                $sub['title'] = $productDownload->getTranslated()['title'];
                $mediaArray[$folderName->getName()][] = $sub;
                if(empty($productDownload->getLanguages()) || !$productDownload->getLanguages()->count()) continue;
                foreach ($productDownload->getLanguages()->getElements() as $languageEntity) {
                    if($languageEntity->getId() === $languageId)
                        continue 2;
                }
                unset($mediaArray[$folderName->getName()]);
                $acrisDownloads->remove($productDownload->getId());
            }
        }
        $event->getContext()->addExtension("myslider",new ArrayEntity(['Values'=> $mediaArray]));
    }
}
