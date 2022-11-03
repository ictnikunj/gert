<?php declare(strict_types=1);

namespace MoorlFormBuilder\Subscriber;

use MoorlFormBuilder\Core\Service\FormService;
use Shopware\Core\Content\Cms\CmsPageEvents;
use Shopware\Core\Content\Media\Event\MediaFileExtensionWhitelistEvent;
use Shopware\Core\Framework\Api\Context\SalesChannelApiSource;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StorefrontSubscriber implements EventSubscriberInterface
{
    private FormService $formService;

    public function __construct(
        FormService $formService
    )
    {
        $this->formService = $formService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CmsPageEvents::SLOT_LOADED_EVENT => 'onEntityLoadedEvent',
            MediaFileExtensionWhitelistEvent::class => 'onMediaFileExtensionWhitelist'
        ];
    }

    public function onMediaFileExtensionWhitelist(MediaFileExtensionWhitelistEvent $event)
    {
        $whitelist = $event->getWhitelist();
        $whitelist = $this->formService->extendFileTypeWhitelist($whitelist);
        $event->setWhitelist($whitelist);
    }

    public function onEntityLoadedEvent(EntityLoadedEvent $event): void
    {
        $source = $event->getContext()->getSource();

        if (!$source instanceof SalesChannelApiSource) {
            return;
        }

        foreach ($event->getEntities() as $entity) {
            if ($entity->getType() === 'moorl-form-builder') {
                $config = $entity->getConfig();
                $formId = $config ? $config['form']['value'] : null;

                if (!$formId) {
                    $config = $entity->getTranslated();
                    $formId = $config['config']['form']['value'];
                    if (!$formId) {
                        throw new \Exception("No form set for the current storefront language");
                    }
                }

                $this->formService->setContext($event->getContext());
                $this->formService->setCheckCache(true);
                $this->formService->initCurrentForm($formId);

                if (!$this->formService->getCurrentForm()) {
                    continue;
                }

                $this->formService->setCheckCache(false);

                $entity->setData($this->formService->getCurrentForm());
            }
        }
    }
}
