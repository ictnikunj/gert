<?php declare(strict_types=1);

namespace MoorlFormBuilder\Core\Service;

use Shopware\Core\Content\Media\Exception\DuplicatedMediaFileNameException;
use Shopware\Core\Content\Media\Exception\EmptyMediaFilenameException;
use Shopware\Core\Content\Media\Exception\IllegalFileNameException;
use Shopware\Core\Content\Media\Exception\MediaNotFoundException;
use Shopware\Core\Content\Media\Exception\UploadException;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\File\MediaFile;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Storefront\Framework\Media\StorefrontMediaValidatorRegistry;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class StorefrontMediaUploader
{
    /**
     * @var FileSaver
     */
    private $fileSaver;

    /**
     * @var MediaService
     */
    private $mediaService;

    /**
     * @var StorefrontMediaValidatorRegistry
     */
    private $validator;

    /**
     * @var int
     */
    private $maxFileSize;

    /**
     * @var int
     */
    private $fileSize;

    /**
     * @var EntityRepositoryInterface
     */
    private $mediaRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $mediaFolderRepository;

    public function __construct(
        EntityRepositoryInterface $mediaRepository,
        EntityRepositoryInterface $mediaFolderRepository,
        MediaService $mediaService,
        FileSaver $fileSaver,
        StorefrontMediaValidatorRegistry $validator
    )
    {
        $this->mediaRepository = $mediaRepository;
        $this->mediaFolderRepository = $mediaFolderRepository;
        $this->mediaService = $mediaService;
        $this->fileSaver = $fileSaver;
        $this->validator = $validator;
        $this->maxFileSize = 0;
        $this->fileSize = 0;
    }

    public function delete($mediaId, $context): bool
    {
        $criteria = new Criteria([$mediaId]);

        if ($this->mediaRepository->search($criteria, $context)->count() === 1) {
            $this->mediaRepository->delete([
                ['id' => $mediaId]
            ], $context);
            return true;
        }

        return false;
    }

    public function validateMultiple(UploadedFile $file, array $types): bool
    {
        foreach ($types as $type) {
            try {
                $this->validator->validate($file, $type);

                return true;
            } catch (\Exception $e) {
                // Do nothing
            }
        }

        return false;
    }

    public function validate(UploadedFile $file, string $type): bool
    {
        if ($type === 'all' || $type === 'custom') {
            return true; // No validation
        }
        if ($type == 'images_documents') {
            return $this->validateMultiple($file, ['documents', 'images']);
        }
        try {
            $this->validator->validate($file, $type);
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * @return int
     */
    public function getMaxFileSize(): int
    {
        return $this->maxFileSize;
    }

    /**
     * @param int $maxFileSize
     */
    public function setMaxFileSize(int $maxFileSize): void
    {
        $this->maxFileSize = $maxFileSize;
    }

    /**
     * @return int
     */
    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function checkSize(UploadedFile $file): bool
    {
        if ($this->maxFileSize === 0) {
            return true; // OK - no limit
        }
        $this->fileSize += $file->getSize();
        if ($this->fileSize < $this->maxFileSize) {
            return true; // OK
        }
        return false; // Max filesize reached
    }

    /**
     * @throws IllegalFileNameException
     * @throws UploadException
     * @throws DuplicatedMediaFileNameException
     * @throws EmptyMediaFilenameException
     */
    public function upload(UploadedFile $file, ?string $folderId, Context $context, bool $isPrivate = false): string
    {
        $this->checkValidFile($file);

        $mediaFile = new MediaFile($file->getPathname(), $file->getMimeType(), $file->getClientOriginalExtension(), $file->getSize());

        $mediaId = $this->createMediaInFolder($folderId, $context, $isPrivate);

        //dump($mediaId);exit;

        try {
            $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($mediaFile, $mediaId, $file): void {
                $this->fileSaver->persistFileToMedia(
                    $mediaFile,
                    $mediaId . '.' . pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                    $mediaId,
                    $context
                );
            });
        } catch (MediaNotFoundException $e) {
            throw new UploadException($e->getMessage());
        }

        return $mediaId;
    }

    private function checkValidFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new UploadException($file->getErrorMessage());
        }

        if (preg_match('/.+\.ph(p([3457s]|-s)?|t|tml)/', $file->getFilename())) {
            throw new IllegalFileNameException($file->getFilename(), 'contains PHP related file extension');
        }
    }

    public function createMediaInFolder(?string $mediaFolderId, Context $context, bool $private = true): string
    {
        if (!$mediaFolderId) {
            $criteria = new Criteria();
            $criteria->addAssociation('defaultFolder');
            $criteria->setLimit(1);
            $defaultFolder = $this->mediaFolderRepository->search($criteria, $context);
            if ($defaultFolder->count() === 1) {
                $mediaFolderId = $defaultFolder->first()->getId();
            }
        }

        $mediaId = Uuid::randomHex();
        $this->mediaRepository->create(
            [
                [
                    'id' => $mediaId,
                    'private' => $private,
                    'mediaFolderId' => $mediaFolderId
                ],
            ],
            $context
        );

        return $mediaId;
    }
}
