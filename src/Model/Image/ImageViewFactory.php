<?php

declare(strict_types=1);

namespace App\Model\Image;

use Shopsys\ReadModelBundle\Image\ImageView;
use Shopsys\ReadModelBundle\Image\ImageViewFactory as BaseImageViewFactory;

class ImageViewFactory extends BaseImageViewFactory
{
    /**
     * @param array $imageData
     * @return \Shopsys\ReadModelBundle\Image\ImageView
     */
    public function createFromImageData(array $imageData): ImageView
    {
        return new ImageView(
            $imageData['id'],
            $imageData['extension'],
            $imageData['entity_name'],
            $imageData['type']
        );
    }
}
