<?php

declare(strict_types=1);

namespace App\Model\Product\View;

use App\Model\Product\Product;
use Shopsys\ReadModelBundle\Image\ImageViewFacade as BaseImageViewFacade;

/**
 * @property \App\Component\Image\ImageFacade $imageFacade
 */
class ImageViewFacade extends BaseImageViewFacade
{
    /**
     * @param int $productId
     * @return \Shopsys\ReadModelBundle\Image\ImageView[]
     */
    public function getStickerViewsByProductId(int $productId): array
    {
        $images = $this->imageFacade->getImagesByEntityIdIndexedById('product', $productId, Product::IMAGE_TYPE_STICKER);

        return array_map(fn ($image) => $this->imageViewFactory->createFromImage($image), $images);
    }
}
