<?php

declare(strict_types=1);

namespace App\Model\Image;

use Shopsys\FrameworkBundle\Component\Image\Image;
use Shopsys\ReadModelBundle\Image\ImageViewFactory as BaseImageViewFactory;

class ImageViewFactory extends BaseImageViewFactory
{
    /**
     * @param array $imageData
     * @return \App\Model\Image\ImageView
     */
    public function createFromImageData(array $imageData): ImageView
    {
        return new ImageView(
            $imageData['id'],
            $imageData['extension'],
            $imageData['entity_name'],
            $imageData['type'],
            $imageData['entity_id'] ?? null
        );
    }

    /**
     * @param \App\Component\Image\Image $image
     * @return \App\Model\Image\ImageView
     */
    public function createFromImage(Image $image): ImageView
    {
        return new ImageView(
            $image->getId(),
            $image->getExtension(),
            $image->getEntityName(),
            $image->getType(),
            $image->getEntityId()
        );
    }
}
