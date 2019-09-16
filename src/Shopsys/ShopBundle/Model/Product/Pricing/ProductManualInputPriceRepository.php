<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Pricing;

use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductManualInputPriceRepository as BaseProductManualInputPriceRepository;
use Shopsys\FrameworkBundle\Model\Product\Product;

class ProductManualInputPriceRepository extends BaseProductManualInputPriceRepository
{
    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup[] $pricingGroups
     * @return \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductManualInputPrice[]
     */
    public function findByProductAndPricingGroups(Product $product, array $pricingGroups)
    {
        return $this->getProductManualInputPriceRepository()->findBy([
            'product' => $product,
            'pricingGroup' => $pricingGroups,
        ]);
    }
}
