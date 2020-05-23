<?php

declare(strict_types=1);

namespace App\Component\Image;

use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;

use Shopsys\FrameworkBundle\Component\Image\ImageFacade as BaseImageFacade;

/**
 * @property \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
 * @property \App\Component\FileUpload\FileUpload $fileUpload
 * @property \App\Component\Image\ImageRepository $imageRepository
 * @method __construct(mixed $imageUrlPrefix, \Doctrine\ORM\EntityManagerInterface $em, \Shopsys\FrameworkBundle\Component\Image\Config\ImageConfig $imageConfig, \App\Component\Image\ImageRepository $imageRepository, \League\Flysystem\FilesystemInterface $filesystem, \App\Component\FileUpload\FileUpload $fileUpload, \Shopsys\FrameworkBundle\Component\Image\ImageLocator $imageLocator, \Shopsys\FrameworkBundle\Component\Image\ImageFactoryInterface $imageFactory, \League\Flysystem\MountManager $mountManager)
 * @method saveImageOrdering(\App\Component\Image\Image[] $orderedImages)
 * @method deleteImages(object $entity, \App\Component\Image\Image[] $images)
 * @method \App\Component\Image\Image getImageByEntity(object $entity, string|null $type)
 * @method \App\Component\Image\Image[] getImagesByEntityIndexedById(object $entity, string|null $type)
 * @method \App\Component\Image\Image[] getAllImagesByEntity(object $entity)
 * @method deleteImageFiles(\App\Component\Image\Image $image)
 * @method string getImageUrl(\Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig, \App\Component\Image\Image|object $imageOrEntity, string|null $sizeName, string|null $type)
 * @method \Shopsys\FrameworkBundle\Component\Image\AdditionalImageData[] getAdditionalImagesData(\Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig, \App\Component\Image\Image $imageOrEntity, string|null $sizeName, string|null $type)
 * @method string getAdditionalImageUrl(\Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig, int $additionalSizeIndex, \App\Component\Image\Image $image, string|null $sizeName)
 * @method \App\Component\Image\Image getImageByObject(\App\Component\Image\Image|object $imageOrEntity, string|null $type)
 * @method \App\Component\Image\Image getById(int $imageId)
 * @method setImagePositionsByOrder(\App\Component\Image\Image[] $orderedImages)
 * @method \App\Component\Image\Image[] getImagesByEntitiesIndexedByEntityId(int[] $entityIds, string $entityClass)
 * @method \App\Component\Image\Image[] getImagesByEntityIdAndNameIndexedById(int $entityId, string $entityName, string|null $type)
 */
class ImageFacade extends BaseImageFacade
{
    /**
     * @param object $entity
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return string[]
     */
    public function getAllImagesUrlsByEntity($entity, DomainConfig $domainConfig): array
    {
        $allImagesUrls = [];
        $allImages = $this->getAllImagesByEntity($entity);

        foreach ($allImages as $image) {
            $allImagesUrls[] = $this->getImageUrl($domainConfig, $image, null, null);
        }

        return $allImagesUrls;
    }

    /**
     * @return int
     */
    public function getHighestImageId(): int
    {
        return $this->imageRepository->getHighestImageId();
    }

    /**
     * @param int $entityId
     * @param string $entityName
     * @param int $imageId
     * @param string $extension
     * @param int|null $position
     * @param string|null $type
     * @param int|null $pohodaId
     */
    public function saveImageIntoDb(
        int $entityId,
        string $entityName,
        int $imageId,
        string $extension,
        ?int $position = null,
        ?string $type = null,
        ?int $pohodaId = null
    ): void {
        $this->imageRepository->saveImageIntoDb($entityId, $entityName, $imageId, $extension, $position, $type, $pohodaId);
    }

    /**
     * @param int $startWithId
     */
    public function restartImagesIdsDbSequence(int $startWithId): void
    {
        $this->imageRepository->restartImagesIdsDbSequence($startWithId);
    }

    /**
     * @param int $pohodaId
     * @return \App\Component\Image\Image|null
     */
    public function findByPohodaId(int $pohodaId): ?Image
    {
        return $this->imageRepository->findByPohodaId($pohodaId);
    }

    /**
     * @param int $imageId
     * @param int $position
     */
    public function updateImagePosition(int $imageId, int $position): void
    {
        $this->imageRepository->updateImagePosition($imageId, $position);
    }

    /**
     * @param int[] $currentPohodaIds
     * @return array
     */
    public function deleteImagesWithNotExistingPohodaId(array $currentPohodaIds): array
    {
        return $this->imageRepository->deleteImagesWithNotExistingPohodaId($currentPohodaIds);
    }
}
