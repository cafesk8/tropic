<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Image;

use Doctrine\ORM\Event\LifecycleEventArgs;
use IOException;
use League\Flysystem\FileExistsException;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\MountManager;
use Shopsys\FrameworkBundle\Component\FileUpload\FileNamingConvention;
use Shopsys\FrameworkBundle\Component\Image\Config\ImageConfig;
use Shopsys\ShopBundle\Component\FileUpload\FileUpload;

class MigrateImageDoctrineListener
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\FileUpload\FileNamingConvention
     */
    private $fileNamingConvention;

    /**
     * @var \Shopsys\ShopBundle\Component\FileUpload\FileUpload
     */
    private $fileUpload;

    /**
     * @var string
     */
    private $migrateProductImagesDir;

    /**
     * @var \League\Flysystem\MountManager
     */
    private $mountManager;

    /**
     * @var \League\Flysystem\FilesystemInterface
     */
    private $filesystem;

    /**
     * @param string $migrateProductImagesDir
     * @param \Shopsys\FrameworkBundle\Component\FileUpload\FileNamingConvention $fileNamingConvention
     * @param \Shopsys\ShopBundle\Component\FileUpload\FileUpload $fileUpload
     * @param \League\Flysystem\MountManager $mountManager
     * @param \League\Flysystem\FilesystemInterface $filesystem
     */
    public function __construct(
        $migrateProductImagesDir,
        FileNamingConvention $fileNamingConvention,
        FileUpload $fileUpload,
        MountManager $mountManager,
        FilesystemInterface $filesystem
    ) {
        $this->migrateProductImagesDir = $migrateProductImagesDir;
        $this->fileNamingConvention = $fileNamingConvention;
        $this->fileUpload = $fileUpload;
        $this->mountManager = $mountManager;
        $this->filesystem = $filesystem;
    }

    /**
     * @param \Doctrine\ORM\Event\LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof Image && $entity->getMigrateFileName() !== null) {
            $this->preFlushImage($entity);
        }
    }

    /**
     * @param \Doctrine\ORM\Event\LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof Image && $entity->getMigrateFileName() !== null) {
            $this->postFlushImage($entity);
        }
    }

    /**
     * @param \Shopsys\ShopBundle\Component\Image\Image $image
     */
    private function preFlushImage(Image $image)
    {
        $image->setFileAsUploaded(Image::UPLOAD_KEY, $image->getMigrateFileName());
    }

    /**
     * @param \Shopsys\ShopBundle\Component\Image\Image $image
     */
    private function postFlushImage(Image $image)
    {
        $migrateFilename = $image->getMigrateFileName();
        $sourceFilepath = $this->migrateProductImagesDir . '/' . $migrateFilename;

        $targetDirectoryPrefix = $image->getType() !== null ? $image->getType() . '/' : '';
        $targetFilename = $this->fileNamingConvention->getFilenameByNamingConvention(
            FileNamingConvention::TYPE_ID,
            $migrateFilename,
            $image->getId()
        );

        $targetFilepath = $this->fileUpload->getTargetFilepathForMigration(
            $targetFilename,
            true,
            $image->getEntityName(),
            $targetDirectoryPrefix . ImageConfig::ORIGINAL_SIZE_NAME
        );

        try {
            if ($this->filesystem->has($targetFilepath)) {
                $this->filesystem->delete($targetFilepath);
            }
            $this->mountManager->copy('local://' . $sourceFilepath, 'main://' . $targetFilepath);
        } catch (FileExistsException | IOException $ex) {
            $message = sprintf(
                'Failed to rename file from migrate directory to entity (from `%s` to `%s`), exception: `%s`',
                $sourceFilepath,
                $targetFilepath,
                $ex->getMessage()
            );
            throw new Exception\MigrateImageToEntityFailedException($message, $ex);
        }
    }
}
