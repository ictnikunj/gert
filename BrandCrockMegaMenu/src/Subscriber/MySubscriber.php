<?php declare(strict_types=1);
/**
* To plugin Subscriber file.
*
* Copyright (C) BrandCrock GmbH. All rights reserved
*
* If you have found this script useful a small
* recommendation as well as a comment on our
* home page(https://brandcrock.com/)
* would be greatly appreciated.
*
* @author BrandCrock GmbH
* @package BrandCrockMegaMenu
*/
namespace Brandcrock\BrandCrockMegaMenu\Subscriber;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Struct\ArrayEntity;
use Shopware\Core\Content\Category\CategoryEvents;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

class MySubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $mediaRepository;
    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    public function __construct(
        EntityRepositoryInterface $EntityRepositoryInterface ,
        SystemConfigService $systemConfigService
    )
    {
        $this->mediaRepository = $EntityRepositoryInterface;
        $this->systemConfigService = $systemConfigService;
    }

    public static function getSubscribedEvents(): array
    {
        return[
            CategoryEvents::CATEGORY_LOADED_EVENT => 'onCategoryLoaded',
        ];
    }

    public function onCategoryLoaded(EntityLoadedEvent $event): void
    {   
        
		if(method_exists($event->getContext()->getSource(), 'getSalesChannelId'))
		{
			$salechannelId  = $event->getContext()->getSource()->getSalesChannelId();
			$bcLimit = $this->systemConfigService->get('BrandCrockMegaMenu.config.bcLimit', $salechannelId);
			$SubMenuHoverEffects = $this->systemConfigService->get('BrandCrockMegaMenu.config.SubMenuHoverEffects', $salechannelId);
			$bcconfigVal['bcLimit']['bcLimit'] = $bcLimit;
			$bcconfigVal['bcLimit']['SubMenuHoverEffects'] = $SubMenuHoverEffects;
			$event->getContext()->addExtension("bcMenuConfig",new ArrayEntity($bcconfigVal));
			$systemConfig = $this->getAssignedConfig($salechannelId);
			$context = $event->getContext();
			$data = $event->getEntities();
			if(!empty($data)){
				foreach($event->getEntities() as $k =>  $entity){
					$urls = [];
					if($entity->getmediaId()!== null && !empty($entity->getmediaId())) {
						$criteria = new Criteria([$entity->getmediaId()]);
						/** @var MediaCollection $media */
						$media = $this->mediaRepository->search($criteria, $context)->getElements();

						foreach ($media as $mediaItem) {
							$urls['bccategoryimage'] = $mediaItem->getUrl();
							$urls['bchovereffect'] = $systemConfig;
							$event->getEntities()[$k]->setExtensions($urls);
						}
					}else{
						$urls['bchovereffect'] = $systemConfig;
						$event->getEntities()[$k]->setExtensions($urls);
					}

				}
			}
		}
    }
    private function getAssignedConfig($salechannelId): string
    {

        $systemConfig= $this->systemConfigService->get('BrandCrockMegaMenu.config.SubMenuHoverEffects' , $salechannelId);
        if($systemConfig == '') {
            $systemConfig = "shine_effect";
        }
        return $systemConfig;
    }

}
