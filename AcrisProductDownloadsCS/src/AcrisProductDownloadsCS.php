<?php declare(strict_types=1);

namespace Acris\ProductDownloads;

use Acris\ImportExport\AcrisImportExport;
use Acris\ImportExport\AcrisImportExportCS;
use Doctrine\DBAL\Connection;
use Shopware\Core\Content\ImportExport\Aggregate\ImportExportLog\ImportExportLogEntity;
use Shopware\Core\Content\ImportExport\ImportExportProfileEntity;
use Shopware\Core\Content\Media\Aggregate\MediaDefaultFolder\MediaDefaultFolderEntity;
use Shopware\Core\Content\Media\Aggregate\MediaFolder\MediaFolderEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\SystemConfig\SystemConfigEntity;

class AcrisProductDownloadsCS extends Plugin
{
    const DEFAULT_PRODUCT_DOWNLOADS_IMPORT_EXPORT_PROFILE_NAME = 'ACRIS Product Downloads';
    const DEFAULT_PRODUCT_DOWNLOADS_IMPORT_EXPORT_NO_IMPORT_PLUGIN_PROFILE_NAME = 'ACRIS Product Downloads ( nur für export )';
    const DEFAULT_PRODUCT_DOWNLOADS_PROCESS_NAME = 'Product downloads import';
    const DEFAULT_PRODUCT_DOWNLOADS_FILE_NAME = 'Product_downloads';
    const DEFAULT_MEDIA_FOLDER_NAME = "Produkt Downloads";
    const DEFAULT_MEDIA_FOLDER_CUSTOM_FIELD = 'acrisProductDownloadsFolder';

    public function uninstall(UninstallContext $context): void
    {
        if ($context->keepUserData()) {
            return;
        }
        $this->cleanupDatabase();
        $this->removeMediaUploadFolder($context->getContext());
        $this->removeImportExportProfiles($context->getContext(), $this->getCriteriaForRemovingImportExportProfiles());
        $this->removeDefaultValuesForImportExportPlugin($context->getContext());
    }

    public function postUpdate(UpdateContext $context): void
    {
        if(version_compare($context->getCurrentPluginVersion(), '1.1.1', '<')) {
            if($context->getPlugin()->isActive() === true) {
                $this->insertDefaultImportExportProfile($context->getContext());
                $this->insertDefaultValuesForImportExportPlugin($context->getContext());
            }
        }

        if(version_compare($context->getCurrentPluginVersion(), '2.1.0', '<')
            && version_compare($context->getUpdatePluginVersion(), '2.1.0', '>=')) {
            if($context->getPlugin()->isActive() === true) {
                $this->insertUpdateConfigValues($context->getContext());
            }
        }

        if(version_compare($context->getCurrentPluginVersion(), '2.1.3', '<')) {
            if($context->getPlugin()->isActive() === true) {
                $this->insertDefaultImportExportProfile($context->getContext());
                $this->removeOldImportExportProfile($context->getContext());
            }
        }

        if(version_compare($context->getCurrentPluginVersion(), '3.3.0', '<')) {
            if($context->getPlugin()->isActive() === true) {
                $this->insertDefaultDownloadTab($context->getContext());
                $this->updateImportExportProfile($context->getContext());
            }
        }
    }

    public function activate(ActivateContext $context): void
    {
        parent::activate($context);
        $this->insertDefaultImportExportProfile($context->getContext());
        $this->insertDefaultValuesForImportExportPlugin($context->getContext());
        $this->insertDefaultDownloadTab($context->getContext());
    }

    private function getDefaultMediaUploadFolder(EntityRepositoryInterface $mediaFolderRepository, Context $context): ?MediaFolderEntity
    {
        return $mediaFolderRepository->search((new Criteria())->addAssociation('media')->addAssociation('configuration')->addAssociation('configuration.mediaFolders')->addFilter(new EqualsFilter('customFields.'.self::DEFAULT_MEDIA_FOLDER_CUSTOM_FIELD, 'true')), $context)->first();
    }

    private function getDefaultMediaDefaultUploadFolder(EntityRepositoryInterface $mediaDefaultFolderRepository, Context $context): ?MediaDefaultFolderEntity
    {
        return $mediaDefaultFolderRepository->search((new Criteria())->addAssociation('folder')->addFilter(new EqualsFilter('customFields.'.self::DEFAULT_MEDIA_FOLDER_CUSTOM_FIELD, 'true')), $context)->first();
    }

    private function updateImportExportProfile(Context $context): void
    {
        /** @var EntityRepositoryInterface $profileRepository */
        $profileRepository = $this->container->get('import_export_profile.repository');

        $profileId = $this->getProfileIdByName($profileRepository, self::DEFAULT_PRODUCT_DOWNLOADS_IMPORT_EXPORT_PROFILE_NAME, $context);
        if (empty($profileId)) return;

        $profileRepository->update([
            [
                'id' => $profileId,
                'mapping' => $this->getDefaultImportProfileMappingData()
            ]
        ], $context);
    }

    private function getDefaultImportProfileMappingData(): array
    {
        return [
            [
                'key' => 'downloadTab.internalId',
                'mappedKey' => 'DownloadTab'
            ],
            [
                'key' => 'products.productNumber',
                'mappedKey' => 'ProductNumber'
            ],
            [
                'key' => 'media.fileName',
                'mappedKey' => 'MediaName'
            ],
            [
                'key' => 'position',
                'mappedKey' => 'Position'
            ],
            [
                'key' => 'translations.DEFAULT.title',
                'mappedKey' => 'Title'
            ],
            [
                'key' => 'translations.DEFAULT.description',
                'mappedKey' => 'Description'
            ],
            [
                'key' => 'languages',
                'mappedKey' => 'Languages'
            ]
        ];
    }

    private function getProfileIdByName(EntityRepositoryInterface $repository, string $profileName, Context $context): ?string
    {
        return $repository->searchIds((new Criteria())->addFilter(new EqualsFilter('name', $profileName)), $context)->firstId();
    }

    private function cleanupDatabase(): void
    {
        $connection = $this->container->get(Connection::class);

        $connection->executeStatement('DROP TABLE IF EXISTS acris_product_download_language');
        $connection->executeStatement('DROP TABLE IF EXISTS acris_product_download_translation');
        $connection->executeStatement('DROP TABLE IF EXISTS acris_product_download');

        $connection->executeStatement('DROP TABLE IF EXISTS acris_download_tab_translation');
        $connection->executeStatement('DROP TABLE IF EXISTS acris_download_tab_rule');
        $connection->executeStatement('DROP TABLE IF EXISTS acris_download_tab');

        $this->removeInheritance($connection, 'rule', 'acrisDownloadTabs');
        $this->removeInheritance($connection, 'product', 'acrisDownloads');
        $this->removeInheritance($connection, 'media', 'acrisDownloads');
        $this->removeInheritance($connection, 'language', 'acrisDownloads');

        $connection->executeStatement('DROP TABLE IF EXISTS acris_product_link_language');
        $connection->executeStatement('DROP TABLE IF EXISTS acris_product_link_translation');
        $connection->executeStatement('DROP TABLE IF EXISTS acris_product_link');

        $this->removeInheritance($connection, 'product', 'acrisLinks');
        $this->removeInheritance($connection, 'language', 'acrisLinks');
    }

    private function removeMediaUploadFolder(Context $context): void
    {
        $mediaFolderRepository = $this->container->get('media_folder.repository');
        $defaultMediaFolderRepository = $this->container->get('media_default_folder.repository');
        $defaultMediaUploadFolder = $this->getDefaultMediaUploadFolder($mediaFolderRepository, $context);
        $defaultMediaDefaultUploadFolder = $this->getDefaultMediaDefaultUploadFolder($defaultMediaFolderRepository, $context);
        if($defaultMediaUploadFolder instanceof MediaFolderEntity) {
            if($defaultMediaUploadFolder->getMedia() && $defaultMediaUploadFolder->getMedia()->count() > 0) {
                return;
            }

            if (!$defaultMediaDefaultUploadFolder instanceof MediaDefaultFolderEntity) {
                return;
            }

            $defaultMediaUploadFolderConfiguration = $defaultMediaUploadFolder->getConfiguration();
            $deleteConfigurationId = null;
            if($defaultMediaUploadFolderConfiguration && $defaultMediaUploadFolderConfiguration->getMediaFolders()) {
                if($defaultMediaUploadFolderConfiguration->getMediaFolders()->count() < 2) {
                    $deleteConfigurationId = $defaultMediaUploadFolderConfiguration->getId();
                }
            }
            $mediaFolderRepository->delete([['id' => $defaultMediaUploadFolder->getId()]], $context);
            $defaultMediaFolderRepository->delete([['id' => $defaultMediaDefaultUploadFolder->getId()]], $context);

            if($deleteConfigurationId !== null) {
                $this->container->get('media_folder_configuration.repository')->delete([['id' => $deleteConfigurationId]], $context);
            }
        }
    }

    private function insertDefaultImportExportProfile(Context $context): void
    {
        $importExportProfileRepository = $this->container->get('import_export_profile.repository');

        $defaultImportExportProfiles = [
            [
                'name' => self::DEFAULT_PRODUCT_DOWNLOADS_IMPORT_EXPORT_PROFILE_NAME,
                'label' => self::DEFAULT_PRODUCT_DOWNLOADS_IMPORT_EXPORT_PROFILE_NAME,
                'systemDefault' => false,
                'sourceEntity' => 'acris_product_download',
                'fileType' => 'text/csv',
                'delimiter' => ';',
                'enclosure' => '"',
                'mapping' => [
                    [
                        'key' => 'products.productNumber',
                        'mappedKey' => 'ProductNumber'
                    ],
                    [
                        'key' => 'media.fileName',
                        'mappedKey' => 'MediaName'
                    ],
                    [
                        'key' => 'position',
                        'mappedKey' => 'Position'
                    ],
                    [
                        'key' => 'translations.DEFAULT.title',
                        'mappedKey' => 'Title'
                    ],
                    [
                        'key' => 'translations.DEFAULT.description',
                        'mappedKey' => 'Description'
                    ],
                    [
                        'key' => 'languages',
                        'mappedKey' => 'Languages'
                    ]
                ]
            ]
        ];

        foreach ($defaultImportExportProfiles as $defaultImportExportProfile) {
            $this->createIfNotExists($importExportProfileRepository, [['name' => 'name', 'value' => $defaultImportExportProfile['name']]], $defaultImportExportProfile, $context);
        }
    }

    protected function removeInheritance(Connection $connection, string $entity, string $propertyName): void
    {
        $sql = str_replace(
            ['#table#', '#column#'],
            [$entity, $propertyName],
            'ALTER TABLE `#table#` DROP `#column#`'
        );

        $connection->executeStatement($sql);
    }

    private function createIfNotExists(EntityRepositoryInterface $repository, array $equalFields, array $data, Context $context): void
    {
        $filters = [];
        foreach ($equalFields as $equalField) {
            $filters[] = new EqualsFilter($equalField['name'], $equalField['value']);
        }
        if(sizeof($filters) > 1) {
            $filter = new MultiFilter(MultiFilter::CONNECTION_OR, $filters);
        } else {
            $filter = array_shift($filters);
        }

        $searchResult = $repository->search((new Criteria())->addFilter($filter), $context);

        if($searchResult->count() == 0) {
            $repository->create([$data], $context);
        } elseif ($searchResult->count() > 0) {
            $data['id'] = $searchResult->first()->getId();
            $repository->update([$data], $context);
        }
    }

    private function removeImportExportProfiles(Context $context, Criteria $criteria): void
    {
        $connection = $this->container->get(Connection::class);

        $importExportProfileRepository = $this->container->get('import_export_profile.repository');
        $importExportLogRepository = $this->container->get('import_export_log.repository');

        /** @var EntitySearchResult $searchResult */
        $searchResult = $importExportProfileRepository->search($criteria, $context);

        $ids = [];
        /** @var \Shopware\Core\Framework\Uuid\Uuid $uuid */
        $uuid = new \Shopware\Core\Framework\Uuid\Uuid();
        if($searchResult->getTotal() > 0 && $searchResult->first()) {

            /** @var ImportExportProfileEntity $entity */
            foreach ($searchResult->getEntities()->getElements() as $entity) {

                if ($entity->getSystemDefault() === true) {
                    $importExportProfileRepository->update([
                        ['id' => $entity->getId(), 'systemDefault' => false ]
                    ], $context);
                }

                /** @var EntitySearchResult $logResult */
                $logResult = $importExportLogRepository->search((new Criteria())->addFilter(new EqualsFilter('profileId', $entity->getId())), $context);
                if ($logResult->getTotal() > 0 && $logResult->first()) {
                    /** @var ImportExportLogEntity $logEntity */
                    foreach ($logResult->getEntities() as $logEntity) {
                        $stmt = $connection->prepare("UPDATE import_export_log SET profile_id = :profileId WHERE id = :id");
                        $stmt->execute(['profileId' => null, 'id' => $uuid::fromHexToBytes($logEntity->getId()) ]);
                    }
                }

                $ids[] = ['id' => $entity->getId()];
            }
            $importExportProfileRepository->delete($ids, $context);
        }
    }

    private function insertDefaultValuesForImportExportPlugin(Context $context): void
    {
        $kernelPluginCollection = $this->container->get('Shopware\Core\Framework\Plugin\KernelPluginCollection');

        /** @var AcrisImportExport $importExportPlugin */
        $importExportPlugin = $kernelPluginCollection->get(AcrisImportExport::class);

        /** @var AcrisImportExportCS $importExportPlugin */
        $importExportPluginCS = $kernelPluginCollection->get(AcrisImportExportCS::class);

        if (($importExportPlugin === null || $importExportPlugin->isActive() === false) && ($importExportPluginCS === null || $importExportPluginCS->isActive() === false)) {
            return;
        }

        $this->insertDefaultIdentifiers($context);
        $this->insertDefaultProcess($context);
    }

    private function insertDefaultIdentifiers(Context $context): void
    {
        /** @var EntityRepositoryInterface $identifierRepository */
        $identifierRepository = $this->container->get('acris_import_export_identifier.repository');

        $defaultIdentifiers = [
            [
                'entity' => 'product',
                'identifier' => 'productNumber',
                'priority' => 10,
                'active' => true
            ],[
                'entity' => 'media',
                'identifier' => 'fileName',
                'priority' => 10,
                'active' => true
            ]
        ];

        foreach ($defaultIdentifiers as $defaultIdentifier) {
            $this->createIdentifierIfNotExists($identifierRepository, $context, $defaultIdentifier);
        }
    }

    private function insertDefaultProcess(Context $context): void
    {
        /** @var EntityRepositoryInterface $processRepository */
        $processRepository = $this->container->get('acris_import_export_process.repository');

        $defaultProductDownloadsProcessFields = [
            [
                'name' => 'id',
                'active' => true,
                'addIfNotExists' => true,
                'conversion' => '$productKey = $data["products"]["productNumber"]; if( array_key_exists($productKey, $this->preparedData) ) { $rowData = $this->preparedData[$productKey]; if ($rowData && array_key_exists(\'productId\', $rowData) && array_key_exists(\'mediaId\', $rowData)) if ($value !== $rowData["productId"] && $value !== $rowData["mediaId"] ) { $value = $rowData["id"]; } }',
                'dataType' => 'string',
                'required' => false
            ],
            [
                'name' => 'media',
                'active' => true,
                'conversion' => 'if ($value && array_key_exists(\'fileName\', $value)) { $fileName = $value[\'fileName\']; $mediaRepository = $this->container->get(\'media.repository\'); $searchResult = $context->disableCache(function ($context) use ($mediaRepository, $fileName) { return $mediaRepository->searchIds((new Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria())->addFilter(new Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter(\'fileName\', $fileName)), $context); }); $mediaId = $searchResult->firstId(); if($mediaId) { $value[\'id\'] = $mediaId; } unset($value[\'fileName\']); }',
                'dataType' => 'array',
                'required' => false
            ]
        ];

        $defaultProcesses = [
            [
                'name' => self::DEFAULT_PRODUCT_DOWNLOADS_PROCESS_NAME,
                'fileName' => self::DEFAULT_PRODUCT_DOWNLOADS_FILE_NAME . ".csv",
                'profileName' => self::DEFAULT_PRODUCT_DOWNLOADS_IMPORT_EXPORT_PROFILE_NAME,
                'mode' => 'import',
                'importType' =>'file',
                'priority' => 10,
                'active' => true,
                'prepareData' => '$rowKey = $row["ProductNumber"]; if ($row && array_key_exists(\'ProductNumber\', $row) && array_key_exists(\'MediaName\', $row)) { $productNumber = $row[\'ProductNumber\']; $productRepository = $this->container->get(\'product.repository\'); $searchResult = $context->disableCache(function ($context) use ($productRepository, $productNumber) { return $productRepository->searchIds((new Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria())->addFilter(new Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter(\'productNumber\', $productNumber)), $context); }); $productId = $searchResult->firstId(); if($productId) { $fileName = $row[\'MediaName\']; $mediaRepository = $this->container->get(\'media.repository\'); $searchResult = $context->disableCache(function ($context) use ($mediaRepository, $fileName) { return $mediaRepository->searchIds((new Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria())->addFilter(new Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter(\'fileName\', $fileName)), $context); }); $mediaId = $searchResult->firstId(); if($mediaId) { $productDownloadsRepository = $this->container->get(\'acris_product_download.repository\'); $searchResult = $context->disableCache(function ($context) use ($productDownloadsRepository, $productId, $mediaId) { return $productDownloadsRepository->searchIds((new Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria())->addFilter(new Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter(\'productId\', $productId))->addFilter(new Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter(\'mediaId\', $mediaId)), $context); }); $productDownloadsId = $searchResult->firstId(); if ($productDownloadsId) { $row[\'productId\'] = $productId; $row[\'mediaId\'] = $mediaId; $row[\'id\'] = $productDownloadsId; } } } } $this->preparedData[$rowKey] = $row;',
                'processFields' => $defaultProductDownloadsProcessFields
            ]
        ];
        foreach ($defaultProcesses as $defaultProcess) {
            $this->createProcessIfNotExists($processRepository, $context, $defaultProcess);
        }

    }

    /**
     * @param EntityRepositoryInterface $entityRepository
     * @param Context $context
     * @param array $identifierData
     */
    private function createIdentifierIfNotExists(EntityRepositoryInterface $entityRepository, Context $context, array $identifierData): void
    {
        $exists = $entityRepository->search((new Criteria())->addFilter(new MultiFilter(MultiFilter::CONNECTION_AND, [new EqualsFilter('entity', $identifierData['entity']), new EqualsFilter('identifier', $identifierData['identifier'])])), $context);
        if($exists->getTotal() === 0) {
            $entityRepository->create([$identifierData], $context);
        }
    }

    /**
     * @param EntityRepositoryInterface $entityRepository
     * @param Context $context
     * @param array $processData
     */
    private function createProcessIfNotExists(EntityRepositoryInterface $entityRepository, Context $context, array $processData): void
    {
        $exists = $entityRepository->search((new Criteria())->addFilter(new EqualsFilter('name', $processData['name'])), $context);
        if($exists->getTotal() === 0) {
            $entityRepository->create([$processData], $context);
        }
    }

    private function removeDefaultValuesForImportExportPlugin(Context $context): void
    {
        $kernelPluginCollection = $this->container->get('Shopware\Core\Framework\Plugin\KernelPluginCollection');

        /** @var AcrisImportExport $importExportPlugin */
        $importExportPlugin = $kernelPluginCollection->get(AcrisImportExport::class);

        /** @var AcrisImportExportCS $importExportPlugin */
        $importExportPluginCS = $kernelPluginCollection->get(AcrisImportExportCS::class);

        if (($importExportPlugin === null || $importExportPlugin->isActive() === false) && ($importExportPluginCS === null || $importExportPluginCS->isActive() === false)) {
            return;
        }

        $this->removeDefaultProcess($context);
    }

    private function removeDefaultProcess(Context $context): void
    {
        /** @var EntityRepositoryInterface $processRepository */
        $processRepository = $this->container->get('acris_import_export_process.repository');

        $searchResult = $processRepository->searchIds((new Criteria())->addFilter(
            new EqualsFilter('name', self::DEFAULT_PRODUCT_DOWNLOADS_PROCESS_NAME)
        ), $context);

        $ids = [];

        if ($searchResult->getTotal() > 0) {
            foreach ($searchResult->getIds() as $id) {
                $ids[] = ['id' => $id];
            }
            $processRepository->delete($ids, $context);
        }
    }

    private function insertUpdateConfigValues(Context $context): void
    {
        $systemConfiguration = $this->container->get('system_config.repository');
        /** @var SystemConfigEntity $configValue */
        $configValue = $systemConfiguration->search((new Criteria())->addFilter(new EqualsFilter('configurationKey', 'AcrisProductDownloadsCS.config.acrisShowDownloadsAsTab')), $context)->first();
        /** @var SystemConfigEntity $configNewValue */
        $configNewValue = $systemConfiguration->search((new Criteria())->addFilter(new EqualsFilter('configurationKey', 'AcrisProductDownloadsCS.config.displayPosition')), $context)->first();
        if (!empty($configValue) && !empty($configValue->getConfigurationValue()) && is_bool($configValue->getConfigurationValue()) && $configValue->getConfigurationValue() === true) {
            if (!empty($configNewValue)) {
                $systemConfiguration->upsert([
                    [
                        'id' => $configNewValue->getId(),
                        'configurationKey' => 'AcrisProductDownloadsCS.config.displayPosition',
                        'configurationValue' => 'afterReviews',
                        'salesChannelId' => null
                    ]
                ], $context);
            } else {
                $systemConfiguration->upsert([
                    [
                        'configurationKey' => 'AcrisProductDownloadsCS.config.displayPosition',
                        'configurationValue' => 'afterReviews',
                        'salesChannelId' => null
                    ]
                ], $context);
            }
        } else {
            if (!empty($configNewValue)) {
                $systemConfiguration->upsert([
                    [
                        'id' => $configNewValue->getId(),
                        'configurationKey' => 'AcrisProductDownloadsCS.config.displayPosition',
                        'configurationValue' => 'noDisplay',
                        'salesChannelId' => null
                    ]
                ], $context);
            } else {
                $systemConfiguration->upsert([
                    [
                        'configurationKey' => 'AcrisProductDownloadsCS.config.displayPosition',
                        'configurationValue' => 'noDisplay',
                        'salesChannelId' => null
                    ]
                ], $context);
            }
        }
    }

    private function removeOldImportExportProfile(Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_AND, [
            new EqualsFilter('sourceEntity', 'acris_product_download'),
            new EqualsFilter('name', self::DEFAULT_PRODUCT_DOWNLOADS_IMPORT_EXPORT_NO_IMPORT_PLUGIN_PROFILE_NAME)
        ]));

        $this->removeImportExportProfiles($context, $criteria);
    }

    private function getCriteriaForRemovingImportExportProfiles(): Criteria
    {
        $criteria = new Criteria();
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
            new EqualsFilter('sourceEntity', 'acris_product_download')
        ]));

        return $criteria;
    }

    private function insertDefaultDownloadTab(Context $context): void
    {
        $downloadTabRepository = $this->container->get('acris_download_tab.repository');
        /** @var IdSearchResult $IdSearchResult */
        $IdSearchResult = $downloadTabRepository->searchIds((new Criteria()), $context);

        if($IdSearchResult->getTotal() == 0) {
            $downloadTabRepository->create($this->getDefaultDownloadTabData(), $context);
        }
    }

    private function getDefaultDownloadTabData(): array
    {
        return [
            [
                'internalId' => 'downloadgroup_1',
                'priority' => 10,
                'translations' => [
                    'en-GB' => [
                        'displayName' => "Default download tab"
                    ],
                    'de-DE' => [
                        'displayName' => "Standard-Download-Registerkarte"
                    ],
                    [
                        'displayName' => "Default download tab",
                        'languageId' => Defaults::LANGUAGE_SYSTEM
                    ]
                ]
            ]
        ];
    }
}
