<?php declare(strict_types=1);

namespace PimImport\Controller;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 */
class ManuallyRunCronController extends AbstractController
{
    public $systemConfigService;
    public $scheduledRepository;
    public $categoryCronSalesChannel;

    const TaskName = "pim.category.cron.channel";

    public function __construct(
        SystemConfigService       $systemConfigService,
        EntityRepositoryInterface  $scheduledRepository,
        EntityRepositoryInterface  $categoryCronSalesChannel
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->scheduledRepository = $scheduledRepository;
        $this->categoryCronSalesChannel = $categoryCronSalesChannel;
    }

    /**
     * @Route("/api/pim/manuallycronmanage", name="api.action.pim.manuallycronmanage", methods={"GET"})
     */
    public function manuallyCronManage(Context $context, Request $request): JsonResponse
    {
        //1 or 2 point is covered
        $salesChannelId = $request->get('salesChannelId');
        $this->systemConfigService->set('PimImport.config.ManageManualCronSalesChannel', $salesChannelId);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', self::TaskName));
        $scheduleObject = $this->scheduledRepository->search($criteria, $context)->first();
        $status = $scheduleObject->getStatus();
        if ($status == 'running') {
            return new JsonResponse([
                'type' => 'error',
                'message' => 'Cron is already running mode',
            ]);
        } elseif ($status == 'queued') {
            return new JsonResponse([
                'type' => 'error',
                'message' => 'Cron is already queued mode',
            ]);
        } else {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));
            $criteria->addFilter(new EqualsFilter('lastUsageAt', date("Y-m-d")));
            $categoryCronSalesChannelObject = $this->categoryCronSalesChannel->search($criteria, $context)->count();
            if ($categoryCronSalesChannelObject >= 1) {
                return new JsonResponse([
                    'type' => 'error',
                    'message' => 'This salesChannel is already run onetime so now selected salesChannel is skip mode.',
                ]);
            } else {
                return new JsonResponse([
                    'type' => 'success',
                    'message' => 'Category Cron is prepared to run for selected SalesChannel',
                ]);
            }
        }
        return new JsonResponse([
            'type' => 'error',
            'message' => 'Please select sales channel',
        ]);
    }
}
