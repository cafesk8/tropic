<?php

declare(strict_types=1);

namespace App\Model\Product\Pricing;

use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductCalculatedPriceRepository as BaseProductCalculatedPriceRepository;
use Shopsys\FrameworkBundle\Model\Product\Product;

/**
 * @property \App\Component\EntityExtension\EntityManagerDecorator $em
 * @method createProductCalculatedPricesForPricingGroup(\App\Model\Pricing\Group\PricingGroup $pricingGroup)
 */
class ProductCalculatedPriceRepository extends BaseProductCalculatedPriceRepository
{
    /**
     * @param \App\Model\Product\Product $product
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $priceWithVat
     */
    public function saveCalculatedPrice(Product $product, PricingGroup $pricingGroup, ?Money $priceWithVat)
    {
        /** @var \App\Model\Product\Pricing\ProductCalculatedPrice|null $productCalculatedPrice */
        $productCalculatedPrice = $this->getProductCalculatedPriceRepository()->find([
            'product' => $product->getId(),
            'pricingGroup' => $pricingGroup->getId(),
        ]);

        if ($productCalculatedPrice === null) {
            $productCalculatedPrice = $this->productCalculatedPriceFactory->create($product, $pricingGroup, $priceWithVat);
            $this->em->persist($productCalculatedPrice);
            $this->em->flush($productCalculatedPrice);
        } elseif ($productCalculatedPrice->getPriceWithVat() !== null && $priceWithVat !== null && $productCalculatedPrice->getPriceWithVat()->equals($priceWithVat) === false
            || $productCalculatedPrice->getPriceWithVat() === null && $priceWithVat !== null
            || $productCalculatedPrice->getPriceWithVat() !== null && $priceWithVat === null
        ) {
            $productCalculatedPrice->setPriceWithVat($priceWithVat);
            $this->em->flush($productCalculatedPrice);
        }

    }
}
