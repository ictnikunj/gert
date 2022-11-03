<?php declare(strict_types=1);

namespace Acris\ProductDownloads\Core\Framework\DataAbstractionLayer\Write;

use Acris\ProductDownloads\Core\Framework\DataAbstractionLayer\Exception\LanguageNotFoundByLocaleException;
use Acris\ProductDownloads\Core\Framework\DataAbstractionLayer\Exception\MediaFileNotFoundException;
use Acris\ProductDownloads\Core\Framework\DataAbstractionLayer\Exception\MediaFolderNotFoundException;
use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Media\Aggregate\MediaDefaultFolder\MediaDefaultFolderEntity;
use Shopware\Core\Content\Media\Exception\DuplicatedMediaFileNameException;
use Shopware\Core\Content\Media\Exception\MediaNotFoundException;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\File\MediaFile;
use Shopware\Core\Content\Product\Exception\ProductNumberNotFoundException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Field;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Computed;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Inherited;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StorageAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\JsonUpdateCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\DataStack\DataStack;
use Shopware\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Shopware\Core\Framework\DataAbstractionLayer\Write\FieldException\WriteFieldException;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\WriteConstraintViolationException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteCommandExtractor as ParentClass;

/**
 * Builds the command queue for write operations.
 *
 * Contains recursive calls from extract->map->AssociationInterface->extract->map->....
 */
class WriteCommandExtractor extends ParentClass
{
    /**
     * @var ParentClass
     */
    private $writeCommandExtractor;

    /**
     * @var DefinitionInstanceRegistry
     */
    private $definitionRegistry;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(
        ParentClass $writeCommandExtractor,
        DefinitionInstanceRegistry $definitionRegistry,
        ContainerInterface $container,
        Connection $connection
    ) {
        $this->writeCommandExtractor = $writeCommandExtractor;
        $this->definitionRegistry = $definitionRegistry;
        $this->container = $container;
        $this->connection = $connection;
    }

    public function extract(array $rawData, WriteParameterBag $parameters): array
    {
        $context = $parameters->getContext()->getContext();

        $definition = $parameters->getDefinition();

        $fields = $this->getFieldsInWriteOrder($definition);

        $pkData = $this->getPrimaryKey($rawData, $parameters, $fields);

        if($definition->getEntityName() === 'acris_product_download' && isset($rawData['media']['fileName']) && !empty($rawData['media']['fileName']) && isset($rawData['products']['productNumber']) && !empty($rawData['products']['productNumber'])) {
            $rawData = $this->getDownloadsData($rawData, $context);
            if (!empty($rawData['fileName'])) {
                $rawData = $this->setImage($rawData, $context,$pkData);
            }
            $rawData = $this->addLanguageIdsIfNecessary($rawData, $context);
        }

        if($definition->getEntityName() === 'acris_product_download' && isset($rawData['fileName']) && !empty($rawData['fileName'])) {
            if (array_key_exists('fileName', $rawData) && !empty($rawData['fileName'])) {
                $rawData = $this->setImage($rawData, $context,$pkData);
                if (!array_key_exists('mediaId', $rawData) || empty($rawData['mediaId'])) {
                    $rawData = $this->checkMediaExistence($rawData, $context);
                }
            }
            $rawData = $this->addLanguageIdsIfNecessary($rawData, $context);
        }

        return $this->writeCommandExtractor->extract($rawData,$parameters);
    }

    public function setProductDownloadMediaId(Context $context, $mediaId, $rawData, $productRaw = null) {
        if (empty($rawData['productId'])) {
            if (!empty($rawData['productId'] = $context->getExtension('acrisProductId')['productId'])) {
                $rawData['productId'] = $context->getExtension('acrisProductId')['productId'];
            } else {
                /** @var EntitySearchResult $product */
                $product = $this->container->get('product.repository')->search((new Criteria())->addFilter(new EqualsFilter('productNumber', $productRaw['productNumber'])), $context);
                if ($product->getTotal() > 0 && $product->first()) {
                    $rawData['productId'] = $product->first()->getId();
                }
            }
        }

        /** @var EntitySearchResult $productDownloadMedia */
        $productDownloadMedia = $this->container->get('acris_product_download.repository')->search((new Criteria())->addFilter(new EqualsFilter('mediaId', $mediaId))->addFilter(new EqualsFilter('productId', $rawData['productId'])), $context);

        if ($productDownloadMedia->first() && $productDownloadMedia->getTotal() > 0) {
            return $productDownloadMedia->first()->getId();
        }
        return false;
    }

    /**
     * @param $rawData
     * @param Context $context
     * @param $parameters
     * @param $definition
     * @param $fields
     * @param $existence
     * @param $pkData
     * @param null $productRaw
     * @return array
     * @throws DuplicatedMediaFileNameException
     * @throws MediaFileNotFoundException
     * @throws MediaFolderNotFoundException
     * @throws MediaNotFoundException
     * @throws \Shopware\Core\Content\Media\Exception\EmptyMediaFilenameException
     * @throws \Shopware\Core\Content\Media\Exception\IllegalFileNameException
     */
    public function setImage($rawData, Context $context, $pkData, $productRaw = null) {
        $systemConfig = $this->container->get('Shopware\Core\System\SystemConfig\SystemConfigService');
        $configValue = $systemConfig->get('AcrisProductDownloadsCS.config.importFilePath');
        if (empty($configValue)) return $rawData;
        $filePath = $configValue . $rawData['fileName'];
        $fileExist = file_exists($filePath);
        $mediaFolderId = $this->getMediaFolderId($context);
        if ($fileExist) {
            $fileSize = filesize($filePath);
            $fileName = pathinfo($filePath)['filename'];
            /** @var EntitySearchResult $mediaRepository */
            $mediaRepository = $this->container->get('media.repository')->search((new Criteria())->addFilter(new EqualsFilter('fileName', $fileName)), $context);
            if ($mediaRepository->getTotal() > 0 && $mediaRepository->first()) {
                if ($fileSize !== $mediaRepository->first()->getFileSize())
                {
                    $mediaRepositoryId = $mediaRepository->first()->getId();
                    $mediaExistence = true;
                    $rawData = $this->updateOrCreateNewMedia($mediaExistence, $filePath, $mediaFolderId, $context, $fileSize, $fileName, $rawData, $mediaRepositoryId,$productRaw);
                } else {
                    $rawData['mediaId'] = $mediaRepository->first()->getId();
                    $productDownloadMediaId = $this->setProductDownloadMediaId($context, $rawData['mediaId'], $rawData, $productRaw);
                    if ($productDownloadMediaId !== false) $rawData['id'] = $productDownloadMediaId;
                    if (array_key_exists('translations', $rawData) && !empty($productDownloadMediaId)) {
                        foreach ($rawData['translations'] as $key => $translation) {
                            if (array_key_exists('acrisProductDownloadId', $translation)) {
                                $rawData['translations'][$key]['acrisProductDownloadId'] = $productDownloadMediaId;
                            }
                        }
                    }
                }
            } else {
                $mediaExistance = false;
                $rawData = $this->updateOrCreateNewMedia($mediaExistance, $filePath, $mediaFolderId, $context, $fileSize, $fileName, $rawData, $pkData,$productRaw);
            }
        } else {
            throw new MediaFileNotFoundException("The file ". $filePath . " could not be found for adding to product downloads!");
        }

        return $rawData;
    }

    /**
     * @param $mediaExistence
     * @param $filePath
     * @param $mediaFolderId
     * @param Context $context
     * @param $fileSize
     * @param $fileName
     * @param array $rawData
     * @param EntityExistence $existence
     * @param WriteParameterBag $parameters
     * @param array $fields
     * @param EntityDefinition $definition
     * @param array $pkData
     * @param null $mediaRepositoryId
     * @return array
     * @throws DuplicatedMediaFileNameException
     * @throws \Shopware\Core\Content\Media\Exception\EmptyMediaFilenameException
     * @throws \Shopware\Core\Content\Media\Exception\IllegalFileNameException
     * @throws \Shopware\Core\Content\Media\Exception\MediaNotFoundException
     */
    private function updateOrCreateNewMedia( $mediaExistence, $filePath, $mediaFolderId, Context $context, $fileSize, $fileName, array $rawData, $mediaRepositoryId = null, $productRaw = null): array
    {
        /** @var FileSaver $fileSaver */
        $fileSaver = $this->container->get('Shopware\Core\Content\Media\File\FileSaver');
        $mimeType = mime_content_type($filePath);
        if ($mediaExistence) {
            $mediaId = $mediaRepositoryId;
            $rawData['id'] = $this->setProductDownloadMediaId($context, $mediaId, $rawData, $productRaw);
            /** @var EntityRepositoryInterface $mediaRepository */
            $mediaRepositoryCreate = $this->container->get('media.repository');
            $mediaRepositoryCreate->upsert(
                [
                    [
                        'id' => $mediaId,
                        'mediaFolderId' => $mediaFolderId
                    ],
                ],
                $context
            );
        } else {
            $mediaId = Uuid::randomHex();
            /** @var EntityRepositoryInterface $mediaRepository */
            $mediaRepositoryCreate = $this->container->get('media.repository');
            $mediaRepositoryCreate->create(
                [
                    [
                        'id' => $mediaId,
                        'mediaFolderId' => $mediaFolderId
                    ],
                ],
                $context
            );
        }


        $fileExtension = pathinfo($filePath)["extension"];
        $mediaFile = new MediaFile($filePath, $mimeType, $fileExtension, $fileSize);
        try {
            $fileSaver->persistFileToMedia($mediaFile, $fileName, $mediaId, $context);
        } catch (DuplicatedMediaFileNameException $e) {
            $fileSaver->persistFileToMedia($mediaFile, $fileName . mb_substr(Uuid::randomHex(), 0, 5), $mediaId, $context);
        }

        $rawData['mediaId'] = $mediaId;
        return $rawData;
    }

    public function extractJsonUpdate($data, EntityExistence $existence, WriteParameterBag $parameters): void
    {
        foreach ($data as $storageName => $attributes) {
            $definition = $this->definitionRegistry->getByEntityName($existence->getEntityName());

            $pks = Uuid::fromHexToBytesList($existence->getPrimaryKey());
            $jsonUpdateCommand = new JsonUpdateCommand(
                $definition,
                $storageName,
                $attributes,
                $pks,
                $existence,
                $parameters->getPath()
            );
            $parameters->getCommandQueue()->add($jsonUpdateCommand->getDefinition(), $jsonUpdateCommand);
        }
    }

    private function map(array $fields, array $rawData, EntityExistence $existence, WriteParameterBag $parameters): array
    {
        $stack = new DataStack($rawData);

        foreach ($fields as $field) {
            $kvPair = $stack->pop($field->getPropertyName());

            // not in data stack?
            if ($kvPair === null) {
                if ($field->is(Inherited::class) && $existence->isChild()) {
                    //inherited field of a child is never required
                    continue;
                }

                $create = !$existence->exists() || $existence->childChangedToParent();

                if (!$create && !$field instanceof UpdatedAtField) {
                    //update statement
                    continue;
                }

                if (!$field->is(Required::class)) {
                    //not required and childhood not changed
                    continue;
                }

                $kvPair = new KeyValuePair($field->getPropertyName(), null, true);
            }

            try {
                if ($field->is(WriteProtected::class)) {
                    $this->validateContextHasPermission($field, $kvPair, $parameters);
                }

                $values = $field->getSerializer()->encode($field, $existence, $kvPair, $parameters);
                foreach ($values as $fieldKey => $fieldValue) {
                    $stack->update($fieldKey, $fieldValue);
                }
            } catch (WriteFieldException $e) {
                $parameters->getContext()->getExceptions()->add($e);
            }
        }

        return $stack->getResultAsArray();
    }

    /**
     * @return Field[]
     */
    private function getFieldsInWriteOrder(EntityDefinition $definition): array
    {
        $fields = $definition->getFields();

        $filtered = [];

        /** @var Field $field */
        foreach ($fields as $field) {
            if ($field->is(Computed::class)) {
                continue;
            }

            $filtered[$field->getExtractPriority()][] = $field;
        }

        krsort($filtered, SORT_NUMERIC);

        $sorted = [];
        foreach ($filtered as $fields) {
            foreach ($fields as $field) {
                $sorted[] = $field;
            }
        }

        return $sorted;
    }

    private function getPrimaryKey(array $rawData, WriteParameterBag $parameters, array $fields): array
    {
        //filter all fields which are relevant to extract the full primary key data
        //this function return additionally, to primary key flagged fields, foreign key fields and many to association
        $mappingFields = $this->getFieldsForPrimaryKeyMapping($fields);

        $existence = new EntityExistence($parameters->getDefinition()->getEntityName(), [], false, false, false, []);

        //run data extraction for only this fields
        $mapped = $this->map($mappingFields, $rawData, $existence, $parameters);

        //after all fields extracted, filter fields to only primary key flagged fields
        $primaryKeys = array_filter($mappingFields, static function (Field $field) {
            return $field->is(PrimaryKey::class);
        });

        $primaryKey = [];

        /** @var StorageAware|Field $field */
        foreach ($primaryKeys as $field) {
            //build new primary key data array which contains only the primary key data
            if (\array_key_exists($field->getStorageName(), $mapped)) {
                $primaryKey[$field->getStorageName()] = $mapped[$field->getStorageName()];
            }
        }

        return $primaryKey;
    }

    /**
     * Returns all fields which are relevant to extract and map the primary key data of an entity definition data array.
     * In case a primary key consist of Foreign Key fields, the corresponding association for these foreign keys must be
     * returned in order to guarantee the creation of these sub entities and to extract the corresponding foreign key value
     * from the nested data array
     *
     * Example: ProductCategoryDefinition
     * Primary key:   product_id, category_id
     *
     * Both fields are defined as foreign key field.
     * It is now possible to create both related entities (product and category), providing a nested data array:
     * [
     *      'product' => ['id' => '..', 'name' => '..'],
     *      'category' => ['id' => '..', 'name' => '..']
     * ]
     *
     * To extract the primary key data of the ProductCategoryDefinition it is required to extract first the product
     * and category association and their foreign key fields.
     *
     * @param Field[] $fields
     *
     * @return Field[]
     */
    private function getFieldsForPrimaryKeyMapping(array $fields): array
    {
        $primaryKeys = array_filter($fields, static function (Field $field) {
            return $field->is(PrimaryKey::class);
        });

        $references = array_filter($fields, static function (Field $field) {
            return $field instanceof ManyToOneAssociationField;
        });

        foreach ($primaryKeys as $primaryKey) {
            if (!$primaryKey instanceof FkField) {
                continue;
            }

            $association = $this->getAssociationByStorageName($primaryKey->getStorageName(), $references);
            if ($association) {
                $primaryKeys[] = $association;
            }
        }

        usort($primaryKeys, static function (Field $a, Field $b) {
            return $b->getExtractPriority() <=> $a->getExtractPriority();
        });

        return $primaryKeys;
    }

    private function getAssociationByStorageName(string $name, array $fields): ?ManyToOneAssociationField
    {
        /** @var ManyToOneAssociationField $association */
        foreach ($fields as $association) {
            if ($association->getStorageName() !== $name) {
                continue;
            }

            return $association;
        }

        return null;
    }

    private function validateContextHasPermission(Field $field, KeyValuePair $data, WriteParameterBag $parameters): void
    {
        /** @var WriteProtected $flag */
        $flag = $field->getFlag(WriteProtected::class);

        if ($flag->isAllowed($parameters->getContext()->getContext()->getScope())) {
            return;
        }

        $message = 'This field is write-protected.';
        $allowedOrigins = '';
        if ($flag->getAllowedScopes()) {
            $message .= ' (Got: "%s" scope and "%s" is required)';
            $allowedOrigins = implode(' or ', $flag->getAllowedScopes());
        }

        $violationList = new ConstraintViolationList();
        $violationList->add(
            new ConstraintViolation(
                sprintf(
                    $message,
                    $parameters->getContext()->getContext()->getScope(),
                    $allowedOrigins
                ),
                $message,
                [
                    $parameters->getContext()->getContext()->getScope(),
                    $allowedOrigins,
                ],
                $data->getValue(),
                $data->getKey(),
                $data->getValue()
            )
        );

        $parameters->getContext()->getExceptions()->add(
            new WriteConstraintViolationException($violationList, $parameters->getPath() . '/' . $data->getKey())
        );
    }

    private function getMediaFolderId(Context $context): string
    {
        $mediaFolderRepository = $this->container->get('media_default_folder.repository');
        /** @var MediaDefaultFolderEntity $defaultMediaFolder */
        $defaultMediaFolder = $mediaFolderRepository->search((new Criteria())->addFilter(new EqualsFilter('entity', 'acris_product_download')), $context)->first();
        if(!$defaultMediaFolder) {
            $defaultMediaFolder = $mediaFolderRepository->search((new Criteria())->addFilter(new EqualsFilter('entity', 'product')), $context)->first();
        }
        if(!$defaultMediaFolder || !$defaultMediaFolder->getFolder()) throw new MediaFolderNotFoundException("No media folder was found for insert new download media file!");
        return $defaultMediaFolder->getFolder()->getId();
    }

    /**
     * @param array $rawData
     * @param Context $context
     * @return array
     * @throws LanguageNotFoundByLocaleException
     * @throws \Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException
     */
    private function addLanguageIdsIfNecessary(array $rawData, Context $context)
    {
        if(array_key_exists('languages', $rawData)) {
            $languageIds = [];

            foreach ($rawData['languages'] as $i => $language) {
                if(empty($language['id']) && !empty($language['language']['localeId'])) {
                    unset($rawData['languages'][$i]);
                    $languageId = $this->getLanguageIdByLocaleCode($language['language']['localeId'], $context);
                    $languageIds[] = $languageId;
                    $rawData['languages'][$i]['languageId'] = $languageId;
                } elseif(isset($language['languageId'])) {
                    $languageIds[] = $language['languageId'];
                }

                if(array_key_exists('downloadId', $language) && array_key_exists('id', $rawData)) {
                    $rawData['languages'][$i]['downloadId'] = $rawData['id'];
                }
            }
            if(isset($rawData['id']) && !empty($rawData['id'])) {
                if(!empty($languageIds)) {
                    $this->connection->executeStatement(
                        'DELETE FROM acris_product_download_language WHERE download_id = :downloadId AND language_id NOT IN (:languageIds)',
                        ['languageIds' => Uuid::fromHexToBytesList($languageIds), 'downloadId' => Uuid::fromHexToBytes($rawData['id'])],
                        ['languageIds' => Connection::PARAM_STR_ARRAY]);
                } else {
                    $this->connection->executeStatement(
                        'DELETE FROM acris_product_download_language WHERE download_id = :downloadId',
                        ['downloadId' => Uuid::fromHexToBytes($rawData['id'])]);
                }
            }
        }
        return $rawData;
    }

    /**
     * @param string $code
     * @param Context $context
     * @return string
     * @throws LanguageNotFoundByLocaleException
     * @throws \Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException
     */
    private function getLanguageIdByLocaleCode(string $localeId, Context $context): string
    {
        $languageRepository = $this->container->get('language.repository');
        $languageId = $languageRepository->searchIds((new Criteria())->addFilter(new EqualsFilter('localeId', $localeId)), $context)->firstId();
        if(empty($languageId)) throw new LanguageNotFoundByLocaleException("The language was not found by the local id $localeId");
        return $languageId;
    }

    private function getDownloadsData(array $rawData, Context $context): array
    {
        $product = $this->container->get('product.repository')->search((new Criteria())->addFilter(new EqualsFilter('productNumber', $rawData['products']['productNumber'])), $context)->first();

        if (empty($product)) throw new ProductNumberNotFoundException($rawData['products']['productNumber']);

        $rawData['productId'] = $product->getId();
        $fileName = pathinfo($rawData['media']['fileName'])['filename'];

        $media = $this->container->get('media.repository')->search((new Criteria())->addFilter(new EqualsFilter('fileName', $fileName)), $context)->first();

        if (empty($media) || $this->checkIfFileExist($rawData['media']['fileName']) === true) {
            $rawData['fileName'] = $rawData['media']['fileName'];
            unset($rawData['mediaId']);
        } else {
            $rawData['mediaId'] = $media->getId();

            // change product download id
            $productDownloadMediaId = $this->setProductDownloadMediaId($context, $rawData['mediaId'], $rawData, null);
            if ($productDownloadMediaId !== false) $rawData['id'] = $productDownloadMediaId;
            if (array_key_exists('translations', $rawData) && !empty($productDownloadMediaId)) {
                foreach ($rawData['translations'] as $key => $translation) {
                    if (array_key_exists('acrisProductDownloadId', $translation)) {
                        $rawData['translations'][$key]['acrisProductDownloadId'] = $productDownloadMediaId;
                    }
                }
            }
        }
        unset($rawData['products']);
        unset($rawData['media']);

        if (array_key_exists('downloadTab', $rawData) && !empty($rawData['downloadTab']) && array_key_exists('internalId', $rawData['downloadTab']) && !empty($rawData['downloadTab']['internalId'])) {
            $downloadTabId = $this->getDownloadTab($rawData['downloadTab']['internalId'], $context);
            if (empty($downloadTabId)) {
                if (array_key_exists('downloadTabId', $rawData)) unset($rawData['downloadTabId']);
            } else {
                $rawData['downloadTabId'] = $downloadTabId;
            }
            unset($rawData['downloadTab']);
        } else {
            if (array_key_exists('downloadTab', $rawData)) unset($rawData['downloadTab']);
            if (array_key_exists('downloadTabId', $rawData)) unset($rawData['downloadTabId']);
        }

        return $rawData;
    }

    private function checkIfFileExist(string $fileName): bool
    {
        $systemConfig = $this->container->get('Shopware\Core\System\SystemConfig\SystemConfigService');
        $configValue = $systemConfig->get('AcrisProductDownloadsCS.config.importFilePath');
        $filePath = $configValue . $fileName;
        return file_exists($filePath);
    }

    private function checkMediaExistence(array $data, Context $context): array
    {
        if (!array_key_exists('fileName', $data) || empty($data['fileName'])) return $data;
        $fileName = pathinfo($data['fileName'])['filename'];

        /** @var EntitySearchResult $mediaRepository */
        $mediaRepository = $this->container->get('media.repository')->search((new Criteria())->addFilter(new EqualsFilter('fileName', $fileName)), $context);
        if ($mediaRepository->getTotal() > 0 && $mediaRepository->first()) {
            $data['mediaId'] = $mediaRepository->first()->getId();
            unset($data['fileName']);
        }

        return $data;
    }

    private function getDownloadTab(string $value, Context $context): ?string
    {
        $validId = Uuid::isValid($value);

        /** @var EntityRepositoryInterface $downloadTabRepository */
        $downloadTabRepository = $this->container->get('acris_download_tab.repository');

        $criteria = $validId ? new Criteria([$value]) : new Criteria();
        if (!$validId) $criteria->addFilter(new EqualsFilter('internalId', $value));

        return $downloadTabRepository->searchIds($criteria, $context)->firstId();
    }
}
