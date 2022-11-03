<?php declare(strict_types=1);

namespace MoorlFormBuilder\Subscriber;

use MoorlFormBuilder\Core\Service\FormService;
use Shopware\Storefront\Page\Account\Order\AccountOrderPageLoadedEvent;
use Shopware\Storefront\Page\Account\Overview\AccountOverviewPageLoadedEvent;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Shopware\Storefront\Page\Account\Profile\AccountProfilePageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AccountSubscriber implements EventSubscriberInterface
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
            AccountOverviewPageLoadedEvent::class => 'onAccountOverviewPageLoaded',
            AccountProfilePageLoadedEvent::class => 'onAccountProfilePageLoaded',
            AccountOrderPageLoadedEvent::class => 'onAccountOrderPageLoadedEvent',
            AccountPaymentMethodPageLoadedEvent::class => 'onAccountPaymentMethodPageLoaded',
            CheckoutFinishPageLoadedEvent::class => 'onCheckoutFinishPageLoaded'
        ];
    }

    public function onAccountOverviewPageLoaded(PageLoadedEvent $event): void
    {
        $this->onAccountPageLoaded($event, 'customerAccountOverview');
    }

    public function onAccountProfilePageLoaded(PageLoadedEvent $event): void
    {
        $this->onAccountPageLoaded($event, 'customerAccountProfile');
    }

    public function onAccountOrderPageLoadedEvent(PageLoadedEvent $event): void
    {
        $this->onAccountPageLoaded($event, 'customerAccountOrder');
    }

    public function onAccountPaymentMethodPageLoaded(PageLoadedEvent $event): void
    {
        $this->onAccountPageLoaded($event, 'customerAccountPaymentMethod');
    }

    public function onCheckoutFinishPageLoaded(PageLoadedEvent $event): void
    {
        $this->onAccountPageLoaded($event, 'checkoutFinish');
    }

    private function onAccountPageLoaded(PageLoadedEvent $event, string $type): void
    {
        $this->formService->setSalesChannelContext($event->getSalesChannelContext());
        $this->formService->initFormsByType($event->getContext(), $type);
        $this->formService->initCurrentForm();

        if (!$this->formService->getCurrentForm()) {
            return;
        }

        $event->getPage()->addExtension('MoorlFormBuilder', $this->formService->getCurrentForm());
    }
}
