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
        $quantifiedItemsDiscounts = $this->initQuantifiedItemsDiscounts($quantifiedItemsPrices);
        $isCertificate = $promoCode !== null && $promoCode->getType() === PromoCodeData::TYPE_CERTIFICATE;
        if ($promoCode === null || $isCertificate === true) {
            return $quantifiedItemsDiscounts;
        }

        $filteredQuantifiedItemsPrices = $this->filterQuantifiedItemsPricesByPromoCode($quantifiedItemsPrices, $promoCode);

        $discountPercentForOrder = $discountPercent !== null ? $discountPercent : '0';
        if ($promoCode->isUseNominalDiscount() === true) {
            $discountPercentForOrder = $this->calculateDiscountPercentFromNominalDiscount(
                $promoCode,
                $filteredQuantifiedItemsPrices
            );
        }

        foreach ($filteredQuantifiedItemsPrices as $quantifiedItemIndex => $quantifiedItemPrice) {
            $quantifiedItemsDiscount = $this->calculateDiscount($quantifiedItemPrice, $discountPercentForOrder);
            $quantifiedItemsDiscounts[$quantifiedItemIndex] = $quantifiedItemsDiscount;
        }

        return $quantifiedItemsDiscounts;
    }

    /**
     * @param array $quantifiedItemsPrices
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price
     */
    private function calculateTotalItemsPrice(array $quantifiedItemsPrices): Price
    {
        $totalItemsPrice = array_reduce(
            $quantifiedItemsPrices,
            function (Price $totalItemsPrice, QuantifiedItemPrice $quantifiedItemsPrice) {
                if ($quantifiedItemsPrice === null) {
                    return $totalItemsPrice;
                }

                return $totalItemsPrice->add($quantifiedItemsPrice->getTotalPrice());
            },
            Price::zero()
        );

        return $totalItemsPrice;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice[] $quantifiedItemsPrices
     * @return array
     */
    private function initQuantifiedItemsDiscounts(array $quantifiedItemsPrices): array
    {
        $quantifiedItemsDiscounts = [];
        foreach (array_keys($quantifiedItemsPrices) as $quantifiedItemIndex) {
            $quantifiedItemsDiscounts[$quantifiedItemIndex] = null;
        }

        return $quantifiedItemsDiscounts;
    }

    /**
     * @param array $quantifiedItemsPrices
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode $promoCode
     * @return \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice[]
     */
    private function filterQuantifiedItemsPricesByPromoCode(array $quantifiedItemsPrices, PromoCode $promoCode): array
    {
        return array_filter(
            $quantifiedItemsPrices,
            function (QuantifiedItemPrice $quantifiedItemsPrice) use ($promoCode) {
                /** @var \Shopsys\ShopBundle\Model\Product\Pricing\ProductPrice $productPrice */
                $productPrice = $quantifiedItemsPrice->getUnitPrice();
                if ($promoCode->getUsageType() === PromoCode::USAGE_TYPE_WITH_ACTION_PRICE) {
                    return $productPrice->isActionPrice() === true;
                }

                if ($promoCode->getUsageType() === PromoCode::USAGE_TYPE_NO_ACTION_PRICE) {
                    return $productPrice->isActionPrice() === false;
                }

                return true;
            }
        );
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode|null $promoCode
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice[] $filteredQuantifiedItemsPrices
     * @return string
     */
    private function calculateDiscountPercentFromNominalDiscount(
        ?PromoCode $promoCode,
        array $filteredQuantifiedItemsPrices
    ): string {
        $discountPercentForOrder = '0';
        /** @var \Shopsys\FrameworkBundle\Model\Pricing\Price $totalItemsPrice */
        $totalItemsPrice = $this->calculateTotalItemsPrice($filteredQuantifiedItemsPrices);

        if ($totalItemsPrice->getPriceWithVat()->isGreaterThan(Money::zero())) {
            $discountPercentForOrder = $promoCode->getNominalDiscount()
                ->divide($totalItemsPrice->getPriceWithVat()->getAmount(), 12)
                ->multiply(100)
                ->getAmount();
        }

        return $discountPercentForOrder;
    }
}
