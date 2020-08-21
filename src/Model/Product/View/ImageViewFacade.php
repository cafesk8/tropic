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
     * @param int|null $warranty
     * @return \Shopsys\ReadModelBundle\Image\ImageView[]
     */
    public function getStickerViewsByProductId(int $productId, ?int $warranty): array
    {
        $images = $this->imageFacade->getImagesByEntityIdIndexedById('product', $productId, Product::IMAGE_TYPE_STICKER);

        if ($warranty !== null && $warranty > 24) {
            $warrantyStickers = $this->imageFacade->getImagesByEntityIdIndexedById('product', 0, Product::IMAGE_TYPE_STICKER);
            array_walk($warrantyStickers, fn ($sticker) => $images[] = $sticker);
        }

        return array_map(fn ($image) => $this->imageViewFactory->createFromImage($image), $images);
    }

    /**
     * @param string $entityClass
     * @param array $entityIds
     * @param string|null $type
     * @return \Shopsys\ReadModelBundle\Image\ImageView[]|null[]
     */
    public function getForEntityIds(string $entityClass, array $entityIds, ?string $type = null): array
    {
        $imagesIndexedByEntityIds = $this->imageFacade->getImagesByEntitiesIndexedByEntityId($entityIds, $entityClass, $type);

        $imageViewsOrNullsIndexedByEntityIds = [];
        foreach ($entityIds as $entityId) {
            $imageOrNull = $this->getImageOrNullFromArray($imagesIndexedByEntityIds, $entityId);

            $imageViewsOrNullsIndexedByEntityIds[$entityId] = $this->createImageViewOrNullFromImage($imageOrNull);
        }

        return $imageViewsOrNullsIndexedByEntityIds;
    }
}
