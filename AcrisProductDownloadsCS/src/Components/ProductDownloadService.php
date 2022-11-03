<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Components;

use Acris\ProductDownloads\Custom\ProductDownloadCollection;
use Acris\ProductDownloads\Custom\ProductDownloadEntity;
use Acris\ProductDownloads\Custom\ProductDownloadTabCollection;
use Acris\ProductDownloads\Custom\ProductDownloadTabEntity;
use Acris\ProductDownloads\Custom\ProductLinkCollection;
use Acris\ProductDownloads\Custom\ProductLinkEntity;
use Shopware\Core\Content\Media\MediaCollection;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Content\Rule\RuleCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ProductDownloadService
{
    const SORTING_ALPHABETICALLY = 'alphabetically';

    private EntityRepositoryInterface $downloadRepository;

    private EntityRepositoryInterface $linkRepository;

    private EntityRepositoryInterface $mediaRepository;

    private SystemConfigService $systemConfigService;

    public function __construct(
        EntityRepositoryInterface $downloadRepository,
        EntityRepositoryInterface $linkRepository,
        EntityRepositoryInterface $mediaRepository,
        SystemConfigService $systemConfigService
    ) {
        $this->downloadRepository = $downloadRepository;
        $this->linkRepository = $linkRepository;
        $this->mediaRepository = $mediaRepository;
        $this->systemConfigService = $systemConfigService;
    }

    public function addProductAssociationCriteria(Criteria $criteria): void
    {
        $criteria->addAssociation('acrisDownloads');
        $criteria->getAssociation('acrisDownloads')
            ->addSorting(new FieldSorting('position'))
            ->addSorting(new FieldSorting('title', FieldSorting::ASCENDING, true))
            ->addAssociation('downloadTab.acrisDownloads.media')
            ->addAssociation('downloadTab.rules')
            ->addAssociation('downloadTab.acrisDownloads.languages')
            ->addAssociation('media')
            ->addAssociation('languages');
        $criteria->getAssociation('acrisDownloads.downloadTab.acrisDownloads')
            ->addSorting(new FieldSorting('position'))
            ->addSorting(new FieldSorting('title', FieldSorting::ASCENDING, true));
    }

    public function addProductLinkAssociationCriteria(Criteria $criteria): void
    {
        $criteria->addAssociation('acrisLinks');
        $criteria->getAssociation('acrisLinks')
            ->addSorting(new FieldSorting('position'))
            ->addSorting(new FieldSorting('title', FieldSorting::ASCENDING, true))
            ->addAssociation('languages');
    }

    public function checkLanguageForProduct(SalesChannelProductEntity $product, string $languageId, SalesChannelContext $context): void
    {
        $acrisDownloads = $product->getExtension('acrisDownloads');

        if($acrisDownloads && $acrisDownloads->count() > 0) {
            if($this->systemConfigService->get('AcrisProductDownloadsCS.config.sortDownloads', $context->getSalesChannel()->getId()) === self::SORTING_ALPHABETICALLY && $acrisDownloads instanceof ProductDownloadCollection) {
                $this->sortCollection($acrisDownloads);
            }

            $mediaIds = [];
            $previewMediaIds = [];
            $downloadIds = [];
            /** @var ProductDownloadEntity $productDownload */
            foreach ($acrisDownloads->getElements() as $productDownload) {
                if (!empty($productDownload->getMediaId())) $mediaIds[] = $productDownload->getMediaId();
                if (!empty($productDownload->getPreviewMediaId())) $previewMediaIds[] = $productDownload->getPreviewMediaId();
                if ($productDownload->getLanguages() && $productDownload->getLanguages()->count() <= 0) $downloadIds[] = $productDownload->getId();
            }

            $downloadCollection = !empty($downloadIds) ? $this->downloadRepository->search((new Criteria($downloadIds))->addAssociation('languages'), $context->getContext())->getEntities() : new ProductDownloadCollection();
            $mediaCollection = !empty($mediaIds) ? $this->mediaRepository->search((new Criteria($mediaIds)), $context->getContext())->getEntities() : new MediaCollection();
            $previewMediaCollection = !empty($previewMediaIds) ? $this->mediaRepository->search((new Criteria($previewMediaIds)), $context->getContext())->getEntities() : new MediaCollection();

            foreach ($acrisDownloads->getElements() as $productDownload) {
                if (!empty($mediaCollection) && !empty($productDownload->getMediaId()) && $mediaCollection->has($productDownload->getMediaId())) {
                    $productDownload->setMedia($mediaCollection->get($productDownload->getMediaId()));
                }

                if (!empty($previewMediaCollection) && !empty($productDownload->getPreviewMediaId()) && $previewMediaCollection->has($productDownload->getPreviewMediaId())) {
                    $productDownload->setPreviewMedia($previewMediaCollection->get($productDownload->getPreviewMediaId()));
                }

                if (!empty($downloadCollection) && !empty($productDownload->getId()) && $downloadCollection->has($productDownload->getId())) {
                    $productDownload->setLanguages($downloadCollection->get($productDownload->getId())->getLanguages());
                }

                if(empty($productDownload->getLanguages()) || $productDownload->getLanguages()->count() === 0) continue;
                foreach ($productDownload->getLanguages()->getElements() as $languageEntity) {
                    if($languageEntity->getId() === $languageId) continue 2;
                }

                $acrisDownloads->remove($productDownload->getId());
            }
        }

        if (!$acrisDownloads instanceof ProductDownloadCollection || $acrisDownloads->count() === 0) return;
        $this->assignDownloadTabs($acrisDownloads, $product, $context);
    }

    public function checkLanguageForProductLinks(SalesChannelProductEntity $product, string $languageId, SalesChannelContext $context): void
    {
        $acrisLinks = $product->getExtension('acrisLinks');
        if($acrisLinks && $acrisLinks->count() > 0) {
            $linkIds = [];
            /** @var ProductLinkEntity $productLink */
            foreach ($acrisLinks->getElements() as $productLink) {
                if ($productLink->getLanguages() && $productLink->getLanguages()->count() <= 0) $linkIds[] = $productLink->getId();
            }

            $linkCollection = !empty($linkIds) ? $this->linkRepository->search((new Criteria($linkIds))->addAssociation('languages'), $context->getContext())->getEntities() : new ProductLinkCollection();

            foreach ($acrisLinks->getElements() as $productLink) {
                if (!empty($linkCollection) && !empty($productLink->getId()) && $linkCollection->has($productLink->getId())) {
                    $productLink->setLanguages($linkCollection->get($productLink->getId())->getLanguages());
                }

                if(empty($productLink->getLanguages()) || $productLink->getLanguages()->count() === 0) continue;
                foreach ($productLink->getLanguages()->getElements() as $languageEntity) {
                    if($languageEntity->getId() === $languageId) continue 2;
                }

                $acrisLinks->remove($productLink->getId());
            }
        }
    }

    private function filterByRules(EntityCollection $collection, SalesChannelContext $salesChannelContext): void
    {
        foreach ($collection->getElements() as $entity) {
            if ($this->rulesValid($entity->getRules(), $salesChannelContext) !== true) {
                $collection->remove($entity->getId());
            }
        }
    }

    private function rulesValid(?RuleCollection $ruleCollection, SalesChannelContext $salesChannelContext): bool
    {
        if (empty($ruleCollection) || $ruleCollection->count() === 0) {
            return true;
        }

        $rules = $salesChannelContext->getRuleIds();
        foreach ($ruleCollection->getElements() as $rule) {
            if (in_array($rule->getId(), $rules)) {
                return true;
            }
        }
        return false;
    }

    private function assignDownloadTabs(ProductDownloadCollection $downloads, SalesChannelProductEntity $product, SalesChannelContext $context): void
    {
        $downloadTabCollection = new ProductDownloadTabCollection();
        /** @var ProductDownloadEntity $download */
        foreach ($downloads as $download) {
            if (!empty($download->getDownloadTab())) {
                if (!$downloadTabCollection->has($download->getDownloadTab()->getId())) {
                    $downloadTabCollection->add($download->getDownloadTab());
                }
                $downloads->remove($download->getId());
            }
        }
        $this->filterByRules($downloadTabCollection, $context);

        if ($downloadTabCollection->count() === 0) return;

        // sort tabs
        $downloadTabCollection->sort(function (ProductDownloadTabEntity $a, ProductDownloadTabEntity $b) {
            $prioA = $a->getPriority() ?? 0;
            $prioB = $b->getPriority() ?? 0;

            return $prioA < $prioB;
        });

        if($this->systemConfigService->get('AcrisProductDownloadsCS.config.sortDownloads', $context->getSalesChannel()->getId()) === self::SORTING_ALPHABETICALLY) {
            foreach ($downloadTabCollection->getElements() as $downloadTab) {
                if ($downloadTab->getAcrisDownloads()->count() > 0) {
                    $this->sortCollection($downloadTab->getAcrisDownloads());
                }
            }
        }

        $product->addExtension('acrisDownloadTabs', $downloadTabCollection);
    }

    private function sortCollection(ProductDownloadCollection $collection): void
    {
        // sort collection
        $collection->sort(static function (ProductDownloadEntity $a, ProductDownloadEntity $b) {
            $aValue = $a->getTranslation('title') ?? $a->getMedia()->getFileName();
            $bValue = $b->getTranslation('title') ?? $b->getMedia()->getFileName();
            return strnatcmp($aValue, $bValue) > 0;
        });
    }
}
