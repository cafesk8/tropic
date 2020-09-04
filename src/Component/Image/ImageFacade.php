<?php

declare(strict_types=1);

namespace App\Component\Image;

use App\Model\Product\Product;
use Shopsys\Cdn\Component\Image\ImageFacade as BaseImageFacade;
use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;

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
        return $this->imageRepository->getImagesByEntityIndexedById($entityName, $entityId, $type);
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
}
