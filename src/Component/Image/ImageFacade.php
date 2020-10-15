<?php

declare(strict_types=1);

namespace App\Component\Image;

use App\Model\Product\Product;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\MountManager;
use Shopsys\Cdn\Component\Image\ImageFacade as BaseImageFacade;
use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Component\FileUpload\FileUpload;
use Shopsys\FrameworkBundle\Component\FileUpload\ImageUploadData;
use Shopsys\FrameworkBundle\Component\Image\Config\ImageConfig;
use Shopsys\FrameworkBundle\Component\Image\Exception\ImageNotFoundException;
use Shopsys\FrameworkBundle\Component\Image\ImageFactoryInterface;
use Shopsys\FrameworkBundle\Component\Image\ImageLocator;
use Shopsys\FrameworkBundle\Component\Image\ImageRepository;

/**
 * @property \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
 * @property \App\Component\FileUpload\FileUpload $fileUpload
 * @property \App\Component\Image\ImageRepository $imageRepository
 * @method \App\Component\Image\Image[] getAllImagesByEntity(object $entity)
 * @method deleteImageFiles(\App\Component\Image\Image $image)
 * @method string getImageUrl(\Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig, \App\Component\Image\Image|object $imageOrEntity, string|null $sizeName, string|null $type)
 * @method \Shopsys\FrameworkBundle\Component\Image\AdditionalImageData[] getAdditionalImagesData(\Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig, \App\Component\Image\Image $imageOrEntity, string|null $sizeName, string|null $type)
 * @method string getAdditionalImageUrl(\Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig, int $additionalSizeIndex, \App\Component\Image\Image $image, string|null $sizeName)
 * @method \App\Component\Image\Image getImageByObject(\App\Component\Image\Image|object $imageOrEntity, string|null $type)
 * @method \App\Component\Image\Image getById(int $imageId)
 * @method setImagePositionsByOrder(\App\Component\Image\Image[] $orderedImages)
 */
class ImageFacade extends BaseImageFacade
{
    /**
     * @var \App\Component\Image\ImageCacheFacade
     */
    private $imageCacheFacade;

    /**
     * @param string $imageUrlPrefix
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Component\Image\Config\ImageConfig $imageConfig
     * @param \App\Component\Image\ImageRepository $imageRepository
     * @param \League\Flysystem\FilesystemInterface $filesystem
     * @param \App\Component\FileUpload\FileUpload $fileUpload
     * @param \Shopsys\FrameworkBundle\Component\Image\ImageLocator $imageLocator
     * @param \Shopsys\FrameworkBundle\Component\Image\ImageFactoryInterface $imageFactory
     * @param \League\Flysystem\MountManager $mountManager
     * @param \App\Component\Image\ImageCacheFacade $imageCacheFacade
     */
    public function __construct(
        $imageUrlPrefix,
        EntityManagerInterface $em,
        ImageConfig $imageConfig,
        ImageRepository $imageRepository,
        FilesystemInterface $filesystem,
        FileUpload $fileUpload,
        ImageLocator $imageLocator,
        ImageFactoryInterface $imageFactory,
        MountManager $mountManager,
        ImageCacheFacade $imageCacheFacade
    ) {
        parent::__construct($imageUrlPrefix, $em, $imageConfig, $imageRepository, $filesystem, $fileUpload, $imageLocator, $imageFactory, $mountManager);

        $this->imageCacheFacade = $imageCacheFacade;
    }

    /**
     * @param object $entity
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return string[]
     */
    public function getAllImagesUrlsByEntity($entity, DomainConfig $domainConfig): array
    {
        $allImagesUrls = [];
        $allImages = $this->getImagesByEntityIndexedById($entity, null);

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
     * @param string|null $description
     */
    public function saveImageIntoDb(
        int $entityId,
        string $entityName,
        int $imageId,
        string $extension,
        ?int $position = null,
        ?string $type = null,
        ?int $pohodaId = null,
        ?string $description = null
    ): void {
        $this->imageRepository->saveImageIntoDb($entityId, $entityName, $imageId, $extension, $position, $type, $pohodaId, $description);
    }

    public function restartImagesIdsDbSequence(): void
    {
        $this->imageRepository->restartImagesIdsDbSequence();
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
     * @param int $imageId
     * @param string|null $description
     */
    public function updateImageDescription(int $imageId, ?string $description): void
    {
        $this->imageRepository->updateImageDescription($imageId, $description);
    }

    /**
     * @param int[] $currentPohodaImageIdsIndexedByProductId
     * @return int[]
     */
    public function deleteImagesWithNotExistingPohodaId(array $currentPohodaImageIdsIndexedByProductId): array
    {
        return $this->imageRepository->deleteImagesWithNotExistingPohodaId($currentPohodaImageIdsIndexedByProductId);
    }

    /**
     * @param string $entityName
     * @param int $entityId
     * @param string|null $type
     * @return \App\Component\Image\Image[]
     */
    public function getImagesByEntityIdIndexedById(string $entityName, int $entityId, ?string $type): array
    {
        return $this->getImagesByEntityIdAndNameIndexedById($entityId, $entityName, $type);
    }

    /**
     * Supplier set items are created from all images (excluding main) whose description contains Product::SUPPLIER_SET_ITEM_NAME_COUNT_SEPARATOR
     *
     * @param \App\Model\Product\Product $product
     * @return \App\Component\Image\Image[]
     */
    public function getSupplierSetItemsImages(Product $product): array
    {
        $images = $this->getImagesByEntityIndexedById($product, null);
        array_shift($images);

        return array_filter($images, function ($image) {
            return $image->getDescription() !== null && strpos($image->getDescription(), Product::SUPPLIER_SET_ITEM_NAME_COUNT_SEPARATOR) !== false;
        });
    }

    /**
     * @param string|null $imageDescription
     * @return string
     */
    public function getSupplierSetItemName(?string $imageDescription): string
    {
        if ($imageDescription === null) {
            return '';
        }
        $separatorPosition = strpos($imageDescription, Product::SUPPLIER_SET_ITEM_NAME_COUNT_SEPARATOR);
        if ($separatorPosition === false) {
            return $imageDescription;
        }

        return substr($imageDescription, 0, $separatorPosition);
    }

    /**
     * @param string|null $imageDescription
     * @return int
     */
    public function getSupplierSetItemCount(?string $imageDescription): int
    {
        if ($imageDescription === null) {
            return 1;
        }
        $separatorPosition = strpos($imageDescription, Product::SUPPLIER_SET_ITEM_NAME_COUNT_SEPARATOR);
        if ($separatorPosition === false) {
            return 1;
        }

        return (int)substr($imageDescription, $separatorPosition + 1);
    }

    /**
     * @param object $entity
     * @param array $temporaryFilenames
     * @param string|null $type
     */
    protected function uploadImage($entity, $temporaryFilenames, $type): void
    {
        if (count($temporaryFilenames) > 0) {
            $entitiesForFlush = [];
            $imageEntityConfig = $this->imageConfig->getImageEntityConfig($entity);
            $entityName = $imageEntityConfig->getEntityName();
            $entityId = $this->getEntityId($entity);
            $oldImage = $this->imageRepository->findImageByEntity($entityName, $entityId, $type);

            if ($oldImage !== null) {
                $this->em->remove($oldImage);
                $entitiesForFlush[] = $oldImage;
            }

            $this->imageCacheFacade->invalidateCacheByEntityNameAndEntityIdAndType($entityName, $entityId, $type);

            $newImage = $this->imageFactory->create(
                $imageEntityConfig->getEntityName(),
                $entityId,
                $type,
                array_pop($temporaryFilenames)
            );
            $this->em->persist($newImage);
            $entitiesForFlush[] = $newImage;

            $this->em->flush($entitiesForFlush);
        }
    }

    /**
     * @param object $entity
     * @param array|null $temporaryFilenames
     * @param string|null $type
     */
    protected function uploadImages($entity, $temporaryFilenames, $type): void
    {
        if ($temporaryFilenames !== null && count($temporaryFilenames) > 0) {
            $imageEntityConfig = $this->imageConfig->getImageEntityConfig($entity);
            $entityName = $imageEntityConfig->getEntityName();
            $entityId = $this->getEntityId($entity);

            $images = $this->imageFactory->createMultiple($imageEntityConfig, $entityId, $type, $temporaryFilenames);
            foreach ($images as $image) {
                $this->em->persist($image);
            }

            $this->imageCacheFacade->invalidateCacheByEntityNameAndEntityIdAndType($entityName, $entityId, $type);

            $this->em->flush();
        }
    }

    /**
     * @param object $entity
     * @param \App\Component\Image\Image[] $images
     */
    protected function deleteImages($entity, array $images): void
    {
        $entityName = $this->imageConfig->getEntityName($entity);
        $entityId = $this->getEntityId($entity);

        // files will be deleted in doctrine listener
        foreach ($images as $image) {
            $image->checkForDelete($entityName, $entityId);
        }

        foreach ($images as $image) {
            $imageToRemove = $this->imageRepository->findById($image->getId());
            $this->imageCacheFacade->invalidateCacheByEntityNameAndEntityIdAndType($entityName, $entityId, null);
            if ($imageToRemove !== null) {
                $this->em->remove($imageToRemove);
            }
        }
    }

    /**
     * @param object $entity
     * @param string|null $type
     * @return \App\Component\Image\Image
     */
    public function getImageByEntity($entity, $type)
    {
        $entityName = $this->imageConfig->getEntityName($entity);
        $entityId = $this->getEntityId($entity);
        $cachedImage = $this->imageCacheFacade->findCachedImageEntityByEntityNameAndEntityIdAndType($entityName, $entityId, $type);

        if ($cachedImage !== null) {
            return $cachedImage;
        }

        $image = $this->imageRepository->getImageByEntity(
            $entityName,
            $entityId,
            $type
        );

        $this->imageCacheFacade->setImageEntityIntoCacheByEntityNameAndEntityIdAndType($entityName, $entityId, $type, $image);

        return $image;
    }

    /**
     * @param object $entity
     * @param string|null $type
     * @return \App\Component\Image\Image[]
     */
    public function getImagesByEntityIndexedById($entity, $type)
    {
        $entityName = $this->imageConfig->getEntityName($entity);
        $entityId = $this->getEntityId($entity);
        $cachedImages = $this->imageCacheFacade->findCachedImageEntitiesByEntityNameAndEntityIdAndType($entityName, $entityId, $type);

        if ($cachedImages !== null) {
            return $cachedImages;
        }

        $imagesByEntity = $this->imageRepository->getImagesByEntityIndexedById(
            $entityName,
            $entityId,
            $type
        );

        $this->imageCacheFacade->setImageEntitiesIntoCacheByEntityNameAndEntityIdAndType($entityName, $entityId, $type, $imagesByEntity);

        return $imagesByEntity;
    }

    /**
     * @param int $entityId
     * @param string $entityName
     * @param string|null $type
     * @return \App\Component\Image\Image[]
     */
    public function getImagesByEntityIdAndNameIndexedById(int $entityId, string $entityName, $type)
    {
        $cachedImages = $this->imageCacheFacade->findCachedImageEntitiesByEntityNameAndEntityIdAndType($entityName, $entityId, $type);

        if ($cachedImages !== null) {
            return $cachedImages;
        }

        $imagesByEntity = $this->imageRepository->getImagesByEntityIndexedById(
            $entityName,
            $entityId,
            $type
        );

        $this->imageCacheFacade->setImageEntitiesIntoCacheByEntityNameAndEntityIdAndType($entityName, $entityId, $type, $imagesByEntity);

        return $imagesByEntity;
    }

    /**
     * @param object $entity
     * @param \Shopsys\FrameworkBundle\Component\FileUpload\ImageUploadData $imageUploadData
     * @param string|null $type
     */
    public function manageImages(object $entity, ImageUploadData $imageUploadData, ?string $type = null): void
    {
        $entityName = $this->imageConfig->getEntityName($entity);
        $entityId = $this->getEntityId($entity);
        $this->imageCacheFacade->invalidateCacheByEntityNameAndEntityIdAndType($entityName, $entityId, $type);

        parent::manageImages($entity, $imageUploadData, $type);
    }

    /**
     * @param array $entityIds
     * @param string $entityClass
     * @param string|null $type
     * @return \App\Component\Image\Image[]
     */
    public function getImagesByEntitiesIndexedByEntityId(array $entityIds, string $entityClass, ?string $type = null): array
    {
        $entityName = $this->imageConfig->getImageEntityConfigByClass($entityClass)->getEntityName();

        return $this->imageRepository->getMainImagesByEntitiesIndexedByEntityId($entityIds, $entityName, $type);
    }

    /**
     * @param string $entityName
     * @param int $entityId
     * @param string|null $type
     * @return \App\Component\Image\Image|null
     */
    public function findImageByEntity(string $entityName, int $entityId, ?string $type): ?Image
    {
        return $this->imageRepository->findImageByEntity($entityName, $entityId, $type);
    }

    /**
     * @param \App\Component\Image\Image[] $orderedImages
     */
    protected function saveImageOrdering($orderedImages): void
    {
        // Image entity can be cached and it caused fatal on flush ("Entity has to be managed or scheduled for removal for single computation")
        $persistedImages = [];
        foreach ($orderedImages as $image) {
            if ($this->em->getUnitOfWork()->isInIdentityMap($image) === true) {
                $persistedImages[] = $image;
            } else {
                try {
                    $persistedImages[] = $this->getById($image->getId());
                } catch (ImageNotFoundException $ex) {
                }
            }
        }
        parent::saveImageOrdering($persistedImages);
    }
}
