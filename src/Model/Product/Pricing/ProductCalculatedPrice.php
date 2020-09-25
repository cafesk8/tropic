<?php

declare(strict_types=1);

namespace App\Model\Product\Pricing;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductCalculatedPrice as BaseProductCalculatedPrice;

/**
 * @ORM\Table(name="product_calculated_prices")
 * @ORM\Entity
 * @property \App\Model\Product\Product $product
 * @property \App\Model\Pricing\Group\PricingGroup $pricingGroup
 * @method __construct(\App\Model\Product\Product $product, \App\Model\Pricing\Group\PricingGroup $pricingGroup, \Shopsys\FrameworkBundle\Component\Money\Money|null $priceWithVat)
 * @method \App\Model\Product\Product getProduct()
 * @method \App\Model\Pricing\Group\PricingGroup getPricingGroup()
 */
class ProductCalculatedPrice extends BaseProductCalculatedPrice
{
    /**
     * @return \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    public function getPriceWithVat(): ?Money
    {
        return $this->priceWithVat;
    }
}
