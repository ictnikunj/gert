<?php declare(strict_types=1);

namespace Ropi\FrontendEditing\ContentEditor\Storage;

use Ropi\ContentEditor\ContentDocument\ContentDocumentInterface;
use Ropi\ContentEditor\Environment\ContentEditorEnvironmentInterface;
use Ropi\ContentEditor\Service\DocumentContextServiceInterface;
use Ropi\ContentEditor\Storage\AbstractContentDocumentStorage;
use Ropi\FrontendEditing\ContentEditor\DocumentContext\DocumentContextUrlBuilder;
use Ropi\FrontendEditing\Core\Content\ContentDocument\ContentDocumentEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\HttpKernel\HttpCache\StoreInterface;

class ContentDocumentStorage extends AbstractContentDocumentStorage
{

    const NUM_MAX_VERSIONS = 128;

    /**
     * @var EntityRepositoryInterface
     */
    protected $contentDocumentRepository;

    /**
     * @var DocumentContextUrlBuilder
     */
    protected $documentContextUrlBuilder;

    /**
     * @var StoreInterface
     */
    protected $cacheStore;

    public function __construct(
        ContentEditorEnvironmentInterface $contentEditorEnvironment,
        DocumentContextServiceInterface $documentContextService,
        EntityRepositoryInterface $contentDocumentRepository,
        DocumentContextUrlBuilder $documentContextUrlBuilder,
        StoreInterface $cacheStore
    ) {
        parent::__construct($contentEditorEnvironment, $documentContextService);

        $this->contentDocumentRepository = $contentDocumentRepository;
        $this->documentContextUrlBuilder = $documentContextUrlBuilder;
        $this->cacheStore = $contentDocumentRepository;
        $this->cacheStore = $cacheStore;
    }

    protected function getContext(): Context
    {
        return Context::createDefaultContext();
    }

    protected function create(): ContentDocumentInterface
    {
        return new ContentDocumentEntity();
    }

    protected function createCriteriaForDocumentContext(array $documentContext): Criteria
    {
        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('salesChannelId', $documentContext['salesChannelId'] ?? null))
            ->addFilter(new EqualsFilter('bundle', $documentContext['bundle'] ?? null))
            ->addFilter(new EqualsFilter('controller', $documentContext['controller'] ?? null))
            ->addFilter(new EqualsFilter('action', $documentContext['action'] ?? ''))
            ->addFilter(new EqualsFilter('languageId', $documentContext['languageId'] ?? null))
            ->addFilter(new EqualsFilter('subcontext', $documentContext['subcontext'] ?? null));

        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));

        unset($documentContext['salesChannelId']);
        unset($documentContext['bundle']);
        unset($documentContext['controller']);
        unset($documentContext['action']);
        unset($documentContext['languageId']);
        unset($documentContext['subcontext']);

        if (empty($documentContext)) {
            return $criteria;
        }

        $this->addAdditionalContextCriteriaFilters($criteria, $documentContext);

        return $criteria;
    }

    /**
     * @param Criteria $criteria
     * @param array $subcontext
     * @param string $fieldPrefix
     */
    protected function addAdditionalContextCriteriaFilters(Criteria $criteria, array $context, string $fieldPrefix = ''): void
    {
        foreach ($context as $key => $value) {
            $field = $fieldPrefix . $key;

            if (is_array($value) || is_object($value)) {
                $this->addAdditionalContextCriteriaFilters($criteria, (array) $value, $field . '.');
                continue;
            }

            $criteria->addFilter(new EqualsFilter($field, $value));
        }
    }

    /**
     * @throws \Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException
     */
    protected function fetchVersion(string $versionId): ?ContentDocumentInterface
    {
        $criteria = new Criteria([$versionId]);

        return $this->contentDocumentRepository->search($criteria, $this->getContext())->getEntities()->get($versionId);
    }

    /**
     * @return ContentDocumentInterface[]
     */
    protected function fetchLatestVersions(array $documentContext): array
    {
        $criteria = $this->createCriteriaForDocumentContext($documentContext)->setLimit(static::NUM_MAX_VERSIONS);

        return $this->contentDocumentRepository->search($criteria, $this->getContext())->getEntities()->getElements();
    }

    protected function fetchCurrent(array $documentContext): ?ContentDocumentInterface
    {
        $criteria = $this->createCriteriaForDocumentContext($documentContext)->setLimit(1);

        return $this->contentDocumentRepository->search($criteria, $this->getContext())->getEntities()->first();
    }

    protected function fetchPublished(array $documentContext): ?ContentDocumentInterface
    {
        $criteria = $this->createCriteriaForDocumentContext($documentContext)
                        ->addFilter(new EqualsFilter('published', true))
                        ->setLimit(1);

        return $this->contentDocumentRepository->search($criteria, $this->getContext())->getEntities()->first();
    }

    protected function persist(ContentDocumentInterface $contentDocument): void
    {
        $this->contentDocumentRepository->upsert(
            [
                [
                    'id' => $contentDocument->getId() ? $contentDocument->getId() : Uuid::randomHex(),
                    'username' => $contentDocument->getUsername(),
                    'structure' => $contentDocument->getStructure(),
                    'published' => $contentDocument->getPublished()
                ]
            ],
            $this->getContext()
        );
    }

    protected function clearCacheForDocumentContext(array $documentContext): void
    {
        $url = $this->documentContextUrlBuilder->build($documentContext, $this->getContext());
        if ($url) {
            $this->cacheStore->purge($url);
        }
    }
}
