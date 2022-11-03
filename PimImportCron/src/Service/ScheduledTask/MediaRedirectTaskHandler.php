<?php declare(strict_types=1);

namespace PimImportCron\Service\ScheduledTask;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\Framework\Uuid\Uuid;

class MediaRedirectTaskHandler extends ScheduledTaskHandler
{
    protected $scheduledTaskRepository;
    private $ictMediaRedirect;
    private $acrisProductDownload;

    public function __construct(
        EntityRepositoryInterface $scheduledTaskRepository,
        EntityRepositoryInterface $ictMediaRedirect,
        EntityRepositoryInterface $acrisProductDownload
    ) {
        parent::__construct($scheduledTaskRepository);
        $this->scheduledTaskRepository = $scheduledTaskRepository;
        $this->ictMediaRedirect = $ictMediaRedirect;
        $this->acrisProductDownload = $acrisProductDownload;
    }

    public static function getHandledMessages(): iterable
    {
        return [ MediaRedirectCronTask::class ];
    }

    public function run(): void
    {
        file_put_contents("MediaImportLog.txt", date("l jS \of F Y h:i:s A")."> Start Media Import\n", FILE_APPEND);

        //collect all media
        $mainArray = [];
        $context = Context::createDefaultContext();
        $mediaDatas =  $this->ictMediaRedirect->search((new Criteria()), $context)->getElements();
        if ($mediaDatas) {
            foreach ($mediaDatas as $mediaData) {
                if ($mediaData->getMediaId()) {
                    $mainArray[] = $mediaData->getMediaId();
                }
            }
        }

        file_put_contents("MediaImportLog.txt", date("l jS \of F Y h:i:s A")."> Main array create complete\n", FILE_APPEND);

        $criteria = new Criteria();
        $criteria->addFilter(
            new MultiFilter(
                'AND',
                [new NotFilter('AND', [new EqualsAnyFilter('mediaId', $mainArray)]),]
            )
        );
        $acrisProductDownload =  $this->acrisProductDownload->search($criteria, $context);

        file_put_contents("MediaImportLog.txt", date("l jS \of F Y h:i:s A")."> Acris array get complete\n", FILE_APPEND);

        if ($acrisProductDownload->getTotal() !== 0) {
            $counter = 1;
            foreach ($acrisProductDownload->getElements() as $acrisDownload) {
                $acrisFileName = $acrisDownload->getMedia()->getfileName();
                $acrisMimeType = $acrisDownload->getMedia()->getfileExtension();
                $mediaId = $acrisDownload->getMediaId();
                if ($acrisFileName && $acrisMimeType) {
                    $filename = "/Data/Environments/000001/Attachment/Bijlage/PRD/ProductImage/lres/".$acrisFileName.'.'.$acrisMimeType;
                    $checkMediaData =  $this->ictMediaRedirect->search(
                        (new Criteria())->addFilter(new EqualsFilter('mediaId', $mediaId)),
                        Context::createDefaultContext()
                    )->count();
                    echo $counter.'--'.$filename;
                    if ($checkMediaData === 0) {
                        $data = [
                            'id' => Uuid::randomHex(),
                            'url' => $filename,
                            'mediaId' => $mediaId,
                        ];
                        file_put_contents(
                            "MediaImportLog.txt",
                            date("l jS \of F Y h:i:s A")."> '.$filename.' Import\n",
                            FILE_APPEND
                        );
                        $this->ictMediaRedirect->upsert([$data], Context::createDefaultContext());
                    }
                }
                $counter++;
            }
        }

        file_put_contents("MediaImportLog.txt", date("l jS \of F Y h:i:s A")."> End Media Import\n", FILE_APPEND);
    }
}
