<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Image;

use Shopsys\FrameworkBundle\Component\Image\Config\ImageEntityConfig;
use Shopsys\FrameworkBundle\Component\Image\ImageFactory as BaseImageFactory;
use Shopsys\FrameworkBundle\Component\Image\Image as BaseImage;

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

    /**
     * @inheritDoc
     */
    public function create(string $entityName, int $entityId, ?string $type, string $temporaryFilename): BaseImage
    {
        $temporaryFilePath = $this->fileUpload->getTemporaryFilepath($temporaryFilename);
        $convertedFilePath = $this->imageProcessor->convertToShopFormatAndGetNewFilename($temporaryFilePath);

        return new Image($entityName, $entityId, $type, $convertedFilePath);
    }
}
