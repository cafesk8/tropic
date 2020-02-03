<?php

declare(strict_types=1);

namespace App\Model\Product;

use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\ProductData;
use Shopsys\FrameworkBundle\Model\Product\ProductFactory as BaseProductFactory;

/**
 * @method \App\Model\Product\Product create(\App\Model\Product\ProductData $data)
 * @method setCalculatedAvailabilityIfMissing(\App\Model\Product\Product $product)
 */
class ProductFactory extends BaseProductFactory
{
    /**
     * @param \App\Model\Product\ProductData $data
     * @param \App\Model\Product\Product $mainProduct
     * @param \App\Model\Product\Product[] $variants
     * @return \App\Model\Product\Product
     */
    public function createMainVariant(ProductData $data, Product $mainProduct, array $variants): Product
    {
        $variants[] = $mainProduct;
        $data->transferNumber = null;

        $classData = $this->entityNameResolver->resolve(Product::class);

        $mainVariant = $classData::createMainVariant($data, $variants);
        $this->setCalculatedAvailabilityIfMissing($mainVariant);

        $mainProduct->setMainVariantGroup(null);

        return $mainVariant;
    }
}
