<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product;

use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\ProductData;
use Shopsys\FrameworkBundle\Model\Product\ProductFactory as BaseProductFactory;

class ProductFactory extends BaseProductFactory
{
    /**
     * @param \Shopsys\ShopBundle\Model\Product\ProductData $data
     * @param \Shopsys\ShopBundle\Model\Product\Product $mainProduct
     * @param \Shopsys\ShopBundle\Model\Product\Product[] $variants
     * @return \Shopsys\ShopBundle\Model\Product\Product
     */
    public function createMainVariant(ProductData $data, Product $mainProduct, array $variants): Product
    {
        $variants[] = $mainProduct;
        $data->transferNumber = null;

        $classData = $this->entityNameResolver->resolve(Product::class);

        $mainVariant = $classData::createMainVariant($data, $this->productCategoryDomainFactory, $variants);
        $this->setCalculatedAvailabilityIfMissing($mainVariant);

        $mainProduct->setMainVariantGroup(null);

        return $mainVariant;
    }
}
