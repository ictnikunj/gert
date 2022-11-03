<?php declare(strict_types=1);

namespace Ropi\FrontendEditing\Storefront\Subscriber;

use Ropi\ContentEditor\Environment\ContentEditorEnvironmentInterface;
use Ropi\ContentEditor\Storage\ContentDocumentStorageInterface;
use Ropi\FrontendEditing\ContentEditor\DocumentContext\DocumentContextBuilder;
use Shopware\Storefront\Event\StorefrontRenderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StorefrontRenderSubscriber implements EventSubscriberInterface
{
    /**
     * @var ContentEditorEnvironmentInterface
     */
    protected $contentEditorEnvironment;

    /**
     * @var DocumentContextBuilder
     */
    protected $documentContextBuilder;

    /**
     * @var ContentDocumentStorageInterface
     */
    protected $contentDocumentStorage;

    public function __construct(
        ContentEditorEnvironmentInterface $contentEditorEnvironment,
        DocumentContextBuilder $documentContextBuilder,
        ContentDocumentStorageInterface $contentDocumentStorage
    ) {
        $this->contentEditorEnvironment = $contentEditorEnvironment;
        $this->documentContextBuilder = $documentContextBuilder;
        $this->contentDocumentStorage = $contentDocumentStorage;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            StorefrontRenderEvent::class => 'onRender'
        ];
    }

    /**
     * @throws \Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException
     */
    public function onRender(StorefrontRenderEvent $event): void
    {
        if ($event->getRequest()->attributes->get('ropi_frontend_editing_content_editor')) {
            return;
        }

        $documentContext = $this->documentContextBuilder->build(
            $event->getRequest(),
            $event->getSalesChannelContext(),
            $event->getParameters()
        );

        $contentDocument = $this->contentDocumentStorage->loadForDocumentContext($documentContext);
        $event->setParameter('ropiFrontendEditingContentDocument', $contentDocument);

        if ($this->contentEditorEnvironment->editorOpened()) {
            $event->setParameter('ropiFrontendEditingEditorOpened', true);
            $event->setParameter('ropiFrontendEditingDocumentContext', $documentContext);

            $documentVersions = $this->contentDocumentStorage->loadLatestVersionsForDocumentContext($documentContext);
            $event->setParameter('ropiFrontendEditingDocumentVersions', $documentVersions);

            if (!$contentDocument->getPublished()) {
                $event->setParameter(
                    'ropiFrontendEditingVersionPreview',
                    $contentDocument->getId() === $this->contentEditorEnvironment->getRequestedVersionId()
                );
            }
        }
    }
}
