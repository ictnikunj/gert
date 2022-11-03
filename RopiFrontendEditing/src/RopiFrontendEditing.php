<?php declare(strict_types=1);

namespace Ropi\FrontendEditing;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\CustomField\CustomFieldTypes;

class RopiFrontendEditing extends Plugin
{

    public function install(Plugin\Context\InstallContext $installContext): void
    {
        parent::install($installContext);

        $this->createCustomFieldSets($installContext->getContext());
    }

    public function uninstall(Plugin\Context\UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        $this->deleteCustomFieldSets($uninstallContext->getContext());
    }

    private function createCustomFieldSets(Context $context): void
    {
        /** @var EntityRepositoryInterface $customFieldSetRepository */
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        $customFieldSetRepository->create([
            [
                'id' => Uuid::randomHex(),
                'name' => 'ropi_frontend_editing_token',
                'config' => [],
                'customFields' => [
                    ['name' => 'ropi_frontend_editing_token', 'type' => CustomFieldTypes::TEXT]
                ]
            ]
        ], $context);
    }

    private function deleteCustomFieldSets(Context $context): void
    {

        /** @var EntityRepositoryInterface $customFieldSetRepository */
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', 'ropi_frontend_editing_token'));

        $result = $customFieldSetRepository->searchIds($criteria, $context);

        foreach ($result->getIds() as $id) {
            $customFieldSetRepository->delete([
                ['id' => $id]
            ], $context);
        }
    }
}