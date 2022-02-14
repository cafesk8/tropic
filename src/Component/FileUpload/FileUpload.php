<?php

declare(strict_types=1);

namespace App\Component\FileUpload;

use App\Component\Domain\DomainHelper;
use League\Flysystem\FileExistsException;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\MountManager;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\FileUpload\EntityFileUploadInterface;
use Shopsys\FrameworkBundle\Component\FileUpload\FileForUpload;
use Shopsys\FrameworkBundle\Component\FileUpload\FileUpload as BaseFileUpload;
use Shopsys\FrameworkBundle\Component\String\TransformString;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * @property \App\Component\FileUpload\FileNamingConvention $fileNamingConvention
 */
class FileUpload extends BaseFileUpload
{
    private Domain $domain;

    /**
     * @param string $temporaryDir
     * @param string $uploadedFileDir
     * @param string $imageDir
     * @param \App\Component\FileUpload\FileNamingConvention $fileNamingConvention
     * @param \League\Flysystem\MountManager $mountManager
     * @param \League\Flysystem\FilesystemInterface $filesystem
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface|null $parameterBag
     */
    public function __construct(
        $temporaryDir,
        $uploadedFileDir,
        $imageDir,
        FileNamingConvention $fileNamingConvention,
        MountManager $mountManager,
        FilesystemInterface $filesystem,
        Domain $domain,
        ?ParameterBagInterface $parameterBag = null
    ) {
        parent::__construct($temporaryDir,
            $uploadedFileDir,
            $imageDir,
            $fileNamingConvention,
            $mountManager,
            $filesystem,
            $parameterBag);
        $this->domain = $domain;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\UploadedFile\UploadedFile|\App\Component\Image\Image $entity
     */
    public function postFlushEntity(EntityFileUploadInterface $entity)
    {
        $filesForUpload = $entity->getTemporaryFilesForUpload();
        foreach ($filesForUpload as $fileForUpload) {
            $sourceFilepath = TransformString::removeDriveLetterFromPath($this->getTemporaryFilepath($fileForUpload->getTemporaryFilename()));
            $originalFilename = $this->fileNamingConvention->getFilenameByNamingConvention(
                $fileForUpload->getNameConventionType(),
                $fileForUpload->getTemporaryFilename(),
                $entity->getId(),
                $entity->getEntityName(),
                $entity->getType(),
                $entity->getEntityId(),
                DomainHelper::CZECH_LOCALE,
                $fileForUpload->isImage()
            );
            $targetFilename = $this->getTargetFilepath(
                $originalFilename,
                $fileForUpload->isImage(),
                $fileForUpload->getCategory(),
                $fileForUpload->getTargetDirectory()
            );

            try {
                if ($this->filesystem->has($targetFilename)) {
                    $this->filesystem->delete($targetFilename);
                }

                $this->mountManager->copy('main://' . $sourceFilepath, 'main://' . $targetFilename);

                if ($entity->getEntityName() === FileNamingConvention::PRODUCT_CLASS_NAME && $entity->getType() === null && $fileForUpload->isImage()) {
                    $this->createImagesForOtherLocales($sourceFilepath, $fileForUpload, $entity);
                }

                if ($this->filesystem->has($sourceFilepath)) {
                    $this->filesystem->delete($sourceFilepath);
                }
            } catch (\Symfony\Component\Filesystem\Exception\IOException $ex) {
                $message = 'Failed to rename file from temporary directory to entity';
                throw new \Shopsys\FrameworkBundle\Component\FileUpload\Exception\MoveToEntityFailedException($message, $ex);
            }
        }
    }

    /**
     * @param string $sourceFilepath
     * @param \Shopsys\FrameworkBundle\Component\FileUpload\FileForUpload $fileForUpload
     * @param \Shopsys\FrameworkBundle\Component\UploadedFile\UploadedFile|\App\Component\Image\Image $entity
     */
    private function createImagesForOtherLocales(string $sourceFilepath, FileForUpload $fileForUpload, EntityFileUploadInterface $entity): void
    {
        $allLocales = $this->domain->getAllLocales();
        foreach ($allLocales as $locale) {
            if ($locale !== DomainHelper::CZECH_LOCALE) {
                $localizedFilename = $this->fileNamingConvention->getFilenameByNamingConvention(
                    $fileForUpload->getNameConventionType(),
                    $fileForUpload->getTemporaryFilename(),
                    $entity->getId(),
                    $entity->getEntityName(),
                    $entity->getType(),
                    $entity->getEntityId(),
                    $locale,
                    $fileForUpload->isImage()
                );
                $localizedFilename = $this->getTargetFilepath(
                    $localizedFilename,
                    $fileForUpload->isImage(),
                    $fileForUpload->getCategory(),
                    $fileForUpload->getTargetDirectory()
                );

                try {
                    $this->mountManager->copy('main://' . $sourceFilepath, 'main://' . $localizedFilename);
                } catch (FileExistsException $exception) {
                    // It's not a problem if a Product has same name in multiple languages
                }
            }
        }
    }
}
