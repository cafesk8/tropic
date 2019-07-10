<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Pricing;

use Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Product\Pricing\QuantifiedProductDiscountCalculation as BaseQuantifiedProductDiscountCalculation;
use Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode;

class QuantifiedProductDiscountCalculation extends BaseQuantifiedProductDiscountCalculation
{
    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice $quantifiedItemPrice
     * @param string|null $discountPercent
     * @param \Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCode|null $promoCode
     * @return \Shopsys\ShopBundle\Model\Pricing\Price|null
     */
    protected function calculateDiscount(QuantifiedItemPrice $quantifiedItemPrice, ?string $discountPercent, ?PromoCode $promoCode = null): ?Price
    {
        if ($discountPercent === null && $promoCode === null) {
            return null;
        }

        $vat = $quantifiedItemPrice->getVat();

        if ($promoCode !== null && $promoCode->isUseNominalDiscount()) {
            $priceWithVat = $promoCode->getNominalDiscount();
        } else {
            $multiplier = (string)($discountPercent / 100);
            $priceWithVat = $this->rounding->roundPriceWithVat(
                $quantifiedItemPrice->getTotalPrice()->getPriceWithVat()->multiply($multiplier)
            );
        }

        if ($priceWithVat->isZero()) {
            return null;
        }

        $priceVatAmount = $this->priceCalculation->getVatAmountByPriceWithVat($priceWithVat, $vat);
        $priceWithoutVat = $priceWithVat->subtract($priceVatAmount);

        return new Price($priceWithoutVat, $priceWithVat);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice[] $quantifiedItemsPrices
     * @param string|null $discountPercent
     * @param \Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCode|null $promoCode
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price[]
     */
    public function calculateDiscounts(array $quantifiedItemsPrices, ?string $discountPercent, ?PromoCode $promoCode = null): array
    {
        $quantifiedItemsDiscounts = [];
        foreach ($quantifiedItemsPrices as $quantifiedItemIndex => $quantifiedItemPrice) {
            $quantifiedItemsDiscounts[$quantifiedItemIndex] = $this->calculateDiscount($quantifiedItemPrice, $discountPercent, $promoCode);
        }

        return $quantifiedItemsDiscounts;
    }
}
