<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Image;

use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;

use Shopsys\FrameworkBundle\Component\Image\ImageFacade as BaseImageFacade;

/**
 * @property \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
 * @property \Shopsys\ShopBundle\Component\FileUpload\FileUpload $fileUpload
 * @method __construct(mixed $imageUrlPrefix, \Doctrine\ORM\EntityManagerInterface $em, \Shopsys\FrameworkBundle\Component\Image\Config\ImageConfig $imageConfig, \Shopsys\FrameworkBundle\Component\Image\ImageRepository $imageRepository, \League\Flysystem\FilesystemInterface $filesystem, \Shopsys\ShopBundle\Component\FileUpload\FileUpload $fileUpload, \Shopsys\FrameworkBundle\Component\Image\ImageLocator $imageLocator, \Shopsys\ShopBundle\Component\Image\ImageFactory $imageFactory, \League\Flysystem\MountManager $mountManager)
 * @method saveImageOrdering(\Shopsys\ShopBundle\Component\Image\Image[] $orderedImages)
 * @method deleteImages(object $entity, \Shopsys\ShopBundle\Component\Image\Image[] $images)
 * @method \Shopsys\ShopBundle\Component\Image\Image getImageByEntity(object $entity, string|null $type)
 * @method \Shopsys\ShopBundle\Component\Image\Image[] getImagesByEntityIndexedById(object $entity, string|null $type)
 * @method \Shopsys\ShopBundle\Component\Image\Image[] getAllImagesByEntity(object $entity)
 * @method deleteImageFiles(\Shopsys\ShopBundle\Component\Image\Image $image)
 * @method string getImageUrl(\Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig, \Shopsys\ShopBundle\Component\Image\Image|object $imageOrEntity, string|null $sizeName, string|null $type)
 * @method \Shopsys\FrameworkBundle\Component\Image\AdditionalImageData[] getAdditionalImagesData(\Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig, \Shopsys\ShopBundle\Component\Image\Image $imageOrEntity, string|null $sizeName, string|null $type)
 * @method string getAdditionalImageUrl(\Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig, int $additionalSizeIndex, \Shopsys\ShopBundle\Component\Image\Image $image, string|null $sizeName)
 * @method \Shopsys\ShopBundle\Component\Image\Image getImageByObject(\Shopsys\ShopBundle\Component\Image\Image|object $imageOrEntity, string|null $type)
 * @method \Shopsys\ShopBundle\Component\Image\Image getById(int $imageId)
 * @method setImagePositionsByOrder(\Shopsys\ShopBundle\Component\Image\Image[] $orderedImages)
 * @method \Shopsys\ShopBundle\Component\Image\Image[] getImagesByEntitiesIndexedByEntityId(int[] $entityIds, string $entityClass)
 */
class ImageFacade extends BaseImageFacade
{
    /**
     * @var \Shopsys\ShopBundle\Component\Image\ImageFactory
     */
    protected $imageFactory;

    /**
     * @param object $entity
     * @param string $migrateFilename
     * @param string|null $type
     */
    public function migrateImage(object $entity, string $migrateFilename, ?string $type): void
    {
        $imageEntityConfig = $this->imageConfig->getImageEntityConfig($entity);
        $entityId = $this->getEntityId($entity);

        $newImage = $this->imageFactory->createImageForMigrate(
            $imageEntityConfig,
            $entityId,
            $migrateFilename,
            $type
        );
        $this->em->persist($newImage);
        $this->em->flush($newImage);
    }

    /**
     * @param object $entity
     */
    public function deleteImagesFromMigration(object $entity): void
    {
        $allImages = $this->getAllImagesByEntity($entity);

        $migratedImages = [];
        /** @var \Shopsys\ShopBundle\Component\Image\Image $image */
        foreach ($allImages as $image) {
            if ($image->getMigrateFileName() !== null) {
                $migratedImages[] = $image;
            }
        }

        $this->deleteImages($entity, $migratedImages);
    }

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
}
