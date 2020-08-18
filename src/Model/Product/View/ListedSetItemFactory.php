<?php

declare(strict_types=1);

namespace App\Model\Product\View;

use App\Model\Image\ImageViewFactory;
use App\Model\Product\Product;
use App\Model\Product\Set\ProductSetFacade;

class ListedSetItemFactory
{
    private ProductSetFacade $productSetFacade;

    private ImageViewFactory $imageViewFactory;

    /**
     * @param \App\Model\Product\Set\ProductSetFacade $productSetFacade
     * @param \App\Model\Image\ImageViewFactory $imageViewFactory
     */
    public function __construct(ProductSetFacade $productSetFacade, ImageViewFactory $imageViewFactory)
    {
        $this->productSetFacade = $productSetFacade;
        $this->imageViewFactory = $imageViewFactory;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param string $locale
     * @return \App\Model\Product\View\ListedSetItem[]
     */
    public function createFromProduct(Product $product, string $locale): array
    {
        $setItemsData = $this->productSetFacade->getAllItemsDataByMainProduct($product, $locale);

        return $this->createFromArray($setItemsData);
    }

    /**
     * @param array $setItemsData
     * @return \App\Model\Product\View\ListedSetItem[]
     */
    public function createFromArray(array $setItemsData): array
    {
        return array_map(function (array $setItemData) {
            $imageView = null;
            $setItemImageData = $setItemData['image'];
            if ($setItemImageData !== null) {
                $imageView = $this->imageViewFactory->createFromImageData($setItemImageData);
            }
            return new ListedSetItem($setItemData['name'], $setItemData['amount'], $imageView);
        }, $setItemsData);
    }
}
