<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Pricing;

use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Product\Pricing\QuantifiedProductDiscountCalculation as BaseQuantifiedProductDiscountCalculation;
use Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode;
use Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeData;

class QuantifiedProductDiscountCalculation extends BaseQuantifiedProductDiscountCalculation
{
    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice[] $quantifiedItemsPrices
     * @param string|null $discountPercent
     * @param \Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCode|null $promoCode
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price[]
     */
    public function calculateDiscounts(array $quantifiedItemsPrices, ?string $discountPercent, ?PromoCode $promoCode = null): array
    {
        $discountPercentForOrder = $discountPercent !== null ? $discountPercent : '0';
        if ($promoCode !== null && $promoCode->isUseNominalDiscount() === true) {

            /** @var \Shopsys\FrameworkBundle\Model\Pricing\Price $totalItemsPrice */
            $totalItemsPrice = array_reduce($quantifiedItemsPrices, function (Price $totalItemsPrice, QuantifiedItemPrice $quantifiedItemsPrice) {
                if ($quantifiedItemsPrice === null) {
                    return $totalItemsPrice;
                }

                return $totalItemsPrice->add($quantifiedItemsPrice->getTotalPrice());
            }, Price::zero());

            if ($totalItemsPrice->getPriceWithVat()->isGreaterThan(Money::zero())) {
                $discountPercentForOrder = $promoCode->getNominalDiscount()->divide($totalItemsPrice->getPriceWithVat()->getAmount(), 12)->multiply(100)->getAmount();
            }
        }

        $quantifiedItemsDiscounts = [];
        foreach ($quantifiedItemsPrices as $quantifiedItemIndex => $quantifiedItemPrice) {
            $quantifiedItemsDiscount = null;
            if ($promoCode !== null && $promoCode->getType() === PromoCodeData::TYPE_PROMO_CODE) {
                $quantifiedItemsDiscount = $this->calculateDiscount($quantifiedItemPrice, $discountPercentForOrder);
            }
            $quantifiedItemsDiscounts[$quantifiedItemIndex] = $quantifiedItemsDiscount;
        }

        return $quantifiedItemsDiscounts;
    }
}
