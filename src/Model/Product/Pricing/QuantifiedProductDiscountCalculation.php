<?php

declare(strict_types=1);

namespace App\Model\Product\Pricing;

use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\Currency;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Pricing\PriceCalculation;
use Shopsys\FrameworkBundle\Model\Pricing\Rounding;
use Shopsys\FrameworkBundle\Model\Product\Pricing\QuantifiedProductDiscountCalculation as BaseQuantifiedProductDiscountCalculation;
use App\Model\Order\PromoCode\PromoCode;
use App\Model\Order\PromoCode\PromoCodeData;
use App\Model\Order\PromoCode\PromoCodeLimitFacade;

/**
 * @method \Shopsys\FrameworkBundle\Model\Pricing\Price|null calculateDiscountRoundedByCurrency(\Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice $quantifiedItemPrice, string $discountPercent, \App\Model\Pricing\Currency\Currency $currency)
 */
class QuantifiedProductDiscountCalculation extends BaseQuantifiedProductDiscountCalculation
{
    /**
     * @var \App\Model\Order\PromoCode\PromoCodeLimitFacade
     */
    private $promoCodeLimitFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\PriceCalculation $priceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Rounding $rounding
     * @param \App\Model\Order\PromoCode\PromoCodeLimitFacade $promoCodeLimitFacade
     */
    public function __construct(PriceCalculation $priceCalculation, Rounding $rounding, PromoCodeLimitFacade $promoCodeLimitFacade)
    {
        parent::__construct($priceCalculation, $rounding);
        $this->promoCodeLimitFacade = $promoCodeLimitFacade;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice[] $quantifiedItemsPrices
     * @param string|null $discountPercent
     * @param \App\Model\Pricing\Currency\Currency $currency
     * @param \App\Model\Order\PromoCode\PromoCode|null $promoCode
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price[]
     */
    public function calculateDiscountsRoundedByCurrency(array $quantifiedItemsPrices, ?string $discountPercent, Currency $currency, ?PromoCode $promoCode = null): array
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

        $maxQuantifiedItemPriceIndex = null;
        $discountNominalAmount = Money::zero();
        foreach ($filteredQuantifiedItemsPrices as $quantifiedItemIndex => $quantifiedItemPrice) {
            $quantifiedItemDiscount = $this->calculateDiscountRoundedByCurrency($quantifiedItemPrice, $discountPercentForOrder, $currency);
            $quantifiedItemsDiscounts[$quantifiedItemIndex] = $quantifiedItemDiscount;
            $discountNominalAmount = $discountNominalAmount->add($quantifiedItemDiscount->getPriceWithVat());

            if ($maxQuantifiedItemPriceIndex === null) {
                $maxQuantifiedItemPriceIndex = $quantifiedItemIndex;
            } elseif ($quantifiedItemsDiscounts[$maxQuantifiedItemPriceIndex]->getPriceWithVat()->isLessThan($quantifiedItemDiscount->getPriceWithVat())) {
                $maxQuantifiedItemPriceIndex = $quantifiedItemIndex;
            }
        }

        if ($promoCode->isUseNominalDiscount() === true && $discountNominalAmount->equals($promoCode->getNominalDiscount()) === false) {
            $nominalDiscountDifferenceAmount = $promoCode->getNominalDiscount()->subtract($discountNominalAmount);
            /** @var \Shopsys\FrameworkBundle\Model\Pricing\Price $maxQuantifiedItemPrice */
            $maxQuantifiedItemPrice = $quantifiedItemsDiscounts[$maxQuantifiedItemPriceIndex];
            $maxQuantifiedItemPrice = $maxQuantifiedItemPrice->add(
                new Price($nominalDiscountDifferenceAmount, $nominalDiscountDifferenceAmount)
            );
            $quantifiedItemsDiscounts[$maxQuantifiedItemPriceIndex] = $maxQuantifiedItemPrice;
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
            function (Price $totalItemsPrice, ?QuantifiedItemPrice $quantifiedItemsPrice) {
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
     * @param \App\Model\Order\PromoCode\PromoCode $promoCode
     * @return \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice[]
     */
    private function filterQuantifiedItemsPricesByPromoCode(array $quantifiedItemsPrices, PromoCode $promoCode): array
    {
        return array_filter(
            $quantifiedItemsPrices,
            function (QuantifiedItemPrice $quantifiedItemsPrice) use ($promoCode) {
                /** @var \App\Model\Product\Pricing\ProductPrice $productPrice */
                $productPrice = $quantifiedItemsPrice->getUnitPrice();
                if ($promoCode->getUsageType() === PromoCode::USAGE_TYPE_WITH_ACTION_PRICE) {
                    return $productPrice->isActionPriceByUsedForPromoCode() === true;
                }

                if ($promoCode->getUsageType() === PromoCode::USAGE_TYPE_NO_ACTION_PRICE) {
                    return $productPrice->isActionPriceByUsedForPromoCode() === false;
                }

                if ($promoCode->getLimitType() === PromoCode::LIMIT_TYPE_ALL) {
                    return true;
                }

                if (!in_array($productPrice->getProductId(), $this->promoCodeLimitFacade->getAllApplicableProductIdsByLimits($promoCode->getLimits()), true)) {
                    return false;
                }

                return true;
            }
        );
    }

    /**
     * @param \App\Model\Order\PromoCode\PromoCode|null $promoCode
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
