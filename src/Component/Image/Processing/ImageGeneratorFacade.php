<?php

declare(strict_types=1);

namespace App\Component\Image\Processing;

use Exception;
use Shopsys\FrameworkBundle\Component\Image\Processing\ImageGeneratorFacade as BaseImageGeneratorFacade;

/**
 * @property \App\Component\Image\ImageRepository $imageRepository
 * @method __construct(\App\Component\Image\ImageRepository $imageRepository, \Shopsys\FrameworkBundle\Component\Image\Processing\ImageGenerator $imageGenerator)
 * @method checkEntityNameAndType(\App\Component\Image\Image $image, string $entityName, null|string $type)
 */
class ImageGeneratorFacade extends BaseImageGeneratorFacade
{
    /**
     * @param string $entityName
     * @param int|string $imageId
     * @param string|null $type
     * @param string|null $sizeName
     * @return string
     */
    public function generateImageAndGetFilepath($entityName, $imageId, $type, $sizeName): string
    {
        if (!is_numeric($imageId)) {
            try {
                $matches = [];
                preg_match('/_([0-9]+)$/', $imageId, $matches);
                $databaseImageId = intval(end($matches));
            } catch (Exception $e) {
                $databaseImageId = $imageId;
            }
        } else {
            $databaseImageId = $imageId;
        }

        $image = $this->imageRepository->getById($databaseImageId);

        $this->checkEntityNameAndType($image, $entityName, $type);

        return $this->imageGenerator->generateImageSizeAndGetFilepath($image, $sizeName);
    }
}
