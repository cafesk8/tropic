<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Image;

use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;

use Shopsys\FrameworkBundle\Component\Image\ImageFacade as BaseImageFacade;

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
     * @return \Shopsys\FrameworkBundle\Component\Image\Image[]
     */
    public function getAllImagesUrlsByEntity($entity, DomainConfig $domainConfig): array
    {
        $allImagesUrls = [];
        $allImages = $this->getAllImagesByEntity($entity);

        foreach ($allImages as $image) {
            $allImagesUrls[] = $this->getImageUrl($domainConfig, $image);
        }

        return $allImagesUrls;
    }
}
