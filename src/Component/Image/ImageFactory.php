<?php

declare(strict_types=1);

namespace App\Component\Image;

use App\Component\Transfer\Pohoda\Product\Image\PohodaImage;
use Shopsys\FrameworkBundle\Component\Image\Config\ImageEntityConfig;
use Shopsys\FrameworkBundle\Component\Image\ImageFactory as BaseImageFactory;

/**
 * @property \App\Component\FileUpload\FileUpload $fileUpload
 * @method __construct(\Shopsys\FrameworkBundle\Component\Image\Processing\ImageProcessor $imageProcessor, \App\Component\FileUpload\FileUpload $fileUpload, \Shopsys\FrameworkBundle\Component\EntityExtension\EntityNameResolver $entityNameResolver)
 * @method \App\Component\Image\Image create(string $entityName, int $entityId, string|null $type, string $temporaryFilename)
 * @method \App\Component\Image\Image[] createMultiple(\Shopsys\FrameworkBundle\Component\Image\Config\ImageEntityConfig $imageEntityConfig, int $entityId, string|null $type, array $temporaryFilenames)
 */
class ImageFactory extends BaseImageFactory
{
    /**
     * @param \Shopsys\FrameworkBundle\Component\Image\Config\ImageEntityConfig $imageEntityConfig
     * @param int $entityId
     * @param string $temporaryFilename
     * @param \App\Component\Transfer\Pohoda\Product\Image\PohodaImage $pohodaImage
     * @param string|null $type
     * @return \App\Component\Image\Image
     */
    public function createFromPohodaImage(
        ImageEntityConfig $imageEntityConfig,
        int $entityId,
        string $temporaryFilename,
        PohodaImage $pohodaImage,
        ?string $type = null
    ): Image {
        $temporaryFilePath = $this->fileUpload->getTemporaryFilepath($temporaryFilename);
        $convertedFilePath = $this->imageProcessor->convertToShopFormatAndGetNewFilename($temporaryFilePath);

        $image = new Image($imageEntityConfig->getEntityName(), $entityId, $type, $convertedFilePath);
        $image->setExtension($pohodaImage->extension);
        $image->setPosition($pohodaImage->position);
        $image->setPohodaId($pohodaImage->id);
        $image->setDescription($pohodaImage->description);

        return $image;
    }
}
