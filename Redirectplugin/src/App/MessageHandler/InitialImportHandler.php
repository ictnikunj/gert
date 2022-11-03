<?php

namespace Redirectplugin\App\MessageHandler;
use Redirectplugin\App\Message\InitialImport;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\MessageQueue\Handler\AbstractMessageHandler;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class InitialImportHandler extends AbstractMessageHandler
{
    /**
     * @var EntityRepositoryInterface
     */
    private $scopPlatformRedirecterRedirectRepository;


    public function __construct(
        EntityRepositoryInterface $scopPlatformRedirecterRedirectRepository
    )
    {
        $this->scopPlatformRedirecterRedirectRepository = $scopPlatformRedirecterRedirectRepository;
    }

    /**
     * @param InitialImport $message
     */
    public function handle($message): void
    {
        $this->getProductData();
    }

    public static function getHandledMessages(): iterable
    {
        return [InitialImport::class];
    }

    /************Get Product Data***************/

    private function getProductData()
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('parentId',null));
        $productDatas = $this->productRepository->search($criteria,Context::createDefaultContext())->getEntities();

        foreach ($productDatas as $productData) {
            $id = $productData->getId();
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('productId',$id));
            $count = $this->priceTrackingRepository->search($criteria,Context::createDefaultContext())->count();

            if($count == 0){
                foreach ($productData->getPrice() as $prices){
                    $grossPrice = $prices->getGross();
                    $netPrice = $prices->getNet();
                }
                $this->priceTrackingRepository->create([
                    [
                        'productId' => $id,
                        'grossPrice' => $grossPrice,
                        'netPrice' => $netPrice,
                    ]
                ], Context::createDefaultContext());
            }
        }
    }
}
