<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Image;

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
}
