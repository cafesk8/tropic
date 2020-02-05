<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Image;

use Shopsys\FrameworkBundle\Component\Image\Config\ImageEntityConfig;
use Shopsys\FrameworkBundle\Component\Image\ImageFactory as BaseImageFactory;

class ImageFactory extends BaseImageFactory
{
    /**
     * @param \Shopsys\FrameworkBundle\Component\Image\Config\ImageEntityConfig $imageEntityConfig
     * @param int $entityId
     * @param string $migrateFilename
     * @param string|null $type
     * @return \Shopsys\ShopBundle\Component\Image\Image
     */
    public function createImageForMigrate(
        ImageEntityConfig $imageEntityConfig,
        int $entityId,
        string $migrateFilename,
        ?string $type
    ): Image {
        $image = new Image($imageEntityConfig->getEntityName(), $entityId, $type, null);
        $image->setMigrateFileName($migrateFilename);

        return $image;
    }
}
