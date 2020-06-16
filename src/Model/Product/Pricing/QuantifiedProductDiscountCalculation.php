<?php

declare(strict_types=1);

namespace App\Model\Product\Pricing;

use App\Model\Order\Discount\OrderDiscountLevel;
use App\Model\Order\PromoCode\PromoCode;
use App\Model\Order\PromoCode\PromoCodeLimitFacade;
use App\Model\Product\Product;
use App\Model\Product\ProductFacade;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\Currency;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Pricing\PriceCalculation;
use Shopsys\FrameworkBundle\Model\Pricing\Rounding;
use Shopsys\FrameworkBundle\Model\Product\Pricing\QuantifiedProductDiscountCalculation as BaseQuantifiedProductDiscountCalculation;

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
     * @var \App\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\PriceCalculation $priceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Rounding $rounding
     * @param \App\Model\Order\PromoCode\PromoCodeLimitFacade $promoCodeLimitFacade
     * @param \App\Model\Product\ProductFacade $productFacade
     */
    public function __construct(PriceCalculation $priceCalculation, Rounding $rounding, PromoCodeLimitFacade $promoCodeLimitFacade, ProductFacade $productFacade)
    {
        parent::__construct($priceCalculation, $rounding);
        $this->promoCodeLimitFacade = $promoCodeLimitFacade;
        $this->productFacade = $productFacade;
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
        $isCertificate = $promoCode !== null && $promoCode->isTypeGiftCertificate();
        if ($promoCode === null || $isCertificate === true) {
            return $quantifiedItemsDiscounts;
        }

        $filteredQuantifiedItemsPrices = $this->filterQuantifiedItemsPricesByPromoCode($quantifiedItemsPrices, $promoCode);

        $discountPercentForOrder = $discountPercent !== null ? $discountPercent : '0';
        if ($promoCode->isUseNominalDiscount()) {
            $discountPercentForOrder = $this->calculateDiscountPercentFromNominalDiscount(
                $promoCode,
                $filteredQuantifiedItemsPrices
            );
        }

        $maxQuantifiedItemPriceIndex = null;
        $discountNominalAmount = Money::zero();
        foreach ($filteredQuantifiedItemsPrices as $quantifiedItemIndex => $quantifiedItemPrice) {
            $quantifiedItemDiscount = $this->calculateDiscountRoundedByCurrency($quantifiedItemPrice, $discountPercentForOrder, $currency);
            $quantifiedItemDiscount = $quantifiedItemDiscount ?? new Price(Money::zero(), Money::zero());
            $quantifiedItemsDiscounts[$quantifiedItemIndex] = $quantifiedItemDiscount;
            $discountNominalAmount = $discountNominalAmount->add($quantifiedItemDiscount->getPriceWithVat());

            if ($maxQuantifiedItemPriceIndex === null) {
                $maxQuantifiedItemPriceIndex = $quantifiedItemIndex;
            } elseif ($quantifiedItemsDiscounts[$maxQuantifiedItemPriceIndex]->getPriceWithVat()->isLessThan($quantifiedItemDiscount->getPriceWithVat())) {
                $maxQuantifiedItemPriceIndex = $quantifiedItemIndex;
            }
        }

        if ($promoCode->isUseNominalDiscount() && $discountNominalAmount->equals($promoCode->getNominalDiscount()) === false) {
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
        return array_reduce(
            $quantifiedItemsPrices,
            function (Price $totalItemsPrice, ?QuantifiedItemPrice $quantifiedItemsPrice) {
                if ($quantifiedItemsPrice === null) {
                    return $totalItemsPrice;
                }

                return $totalItemsPrice->add($quantifiedItemsPrice->getTotalPrice());
            },
            Price::zero()
        );
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
                $product = $this->productFacade->getById($productPrice->getProductId());

                if (($product->isPromoDiscountDisabled() || $product->isInAnySaleStock()) && !$promoCode->isTypeGiftCertificate()) {
                    return false;
                }

                if ($promoCode->getLimitType() !== PromoCode::LIMIT_TYPE_ALL
                    &&
                    !in_array(
                        $productPrice->getProductId(),
                        array_map(function (Product $product) {
                            return $product->getId();
                        }, $this->promoCodeLimitFacade->getAllApplicableProductsByLimits($promoCode->getLimits())),
                        true
                    )
                ) {
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

    /**
     * Inspired by original calculateDiscountsRoundedByCurrency (@see \App\Model\Product\Pricing\QuantifiedProductDiscountCalculation)
     * The original method is already overridden to work with extended promo codes
     *
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice[] $quantifiedItemsPrices
     * @param \App\Model\Pricing\Currency\Currency $currency
     * @param \App\Model\Order\Discount\OrderDiscountLevel|null $orderDiscountLevel
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price[]
     */
    public function calculateQuantifiedItemsDiscountsRoundedByCurrency(
        array $quantifiedItemsPrices,
        Currency $currency,
        ?OrderDiscountLevel $orderDiscountLevel
    ): array {
        if ($orderDiscountLevel === null) {
            return [];
        }
        $filteredQuantifiedItemsPrices = $this->filterQuantifiedItemsPricesByDiscountExclusion($quantifiedItemsPrices);
        $quantifiedItemsDiscounts = [];
        foreach ($filteredQuantifiedItemsPrices as $quantifiedItemIndex => $quantifiedItemPrice) {
            $quantifiedItemsDiscounts[$quantifiedItemIndex] = $this->calculateDiscountRoundedByCurrency(
                $quantifiedItemPrice,
                (string)$orderDiscountLevel->getDiscountPercent(),
                $currency
            );
        }

        return $quantifiedItemsDiscounts;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice[] $quantifiedItemsPrices
     * @return \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice[]
     */
    private function filterQuantifiedItemsPricesByDiscountExclusion(array $quantifiedItemsPrices): array
    {
        return array_filter(
            $quantifiedItemsPrices,
            function (QuantifiedItemPrice $quantifiedItemsPrice) {
                /** @var \App\Model\Product\Pricing\ProductPrice $productPrice */
                $productPrice = $quantifiedItemsPrice->getUnitPrice();
                $product = $this->productFacade->getById($productPrice->getProductId());

                if ($product->isPromoDiscountDisabled() || $product->isInAnySaleStock()) {
                    return false;
                }

                return true;
            }
        );
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice[] $quantifiedItemsPrices
     * @param \App\Model\Order\PromoCode\PromoCode[] $promoCodes
     * @param \App\Model\Pricing\Currency\Currency $currency
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price[][]
     */
    public function getQuantifiedItemsDiscountsIndexedByPromoCodeId(array $quantifiedItemsPrices, array $promoCodes, Currency $currency): array
    {
        $quantifiedItemsDiscountsIndexedByPromoCodeId = [];
        $quantifiedItemsPricesForDiscountsCalculation = $quantifiedItemsPrices;
        foreach ($promoCodes as $promoCode) {
            $quantifiedItemsPricesForDiscountsCalculation = $this->getQuantifiedItemsPricesMinusAlreadyAppliedDiscounts(
                $quantifiedItemsPricesForDiscountsCalculation,
                $quantifiedItemsDiscountsIndexedByPromoCodeId
            );
            $quantifiedItemsDiscountsIndexedByPromoCodeId[$promoCode->getId()] = $this->calculateDiscountsRoundedByCurrency(
                $quantifiedItemsPricesForDiscountsCalculation,
                $promoCode->getPercent(),
                $currency,
                $promoCode
            );
        }

        return $quantifiedItemsDiscountsIndexedByPromoCodeId;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice[] $quantifiedItemsPrices
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price[][]|mixed[][] $quantifiedItemsDiscountsIndexedByPromoCodeId
     * @return \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice[]
     */
    private function getQuantifiedItemsPricesMinusAlreadyAppliedDiscounts(array $quantifiedItemsPrices, array $quantifiedItemsDiscountsIndexedByPromoCodeId)
    {
        if (empty($quantifiedItemsDiscountsIndexedByPromoCodeId)) {
            return $quantifiedItemsPrices;
        }

        $quantifiedItemsPricesMinusAlreadyAppliedDiscounts = [];
        foreach ($quantifiedItemsDiscountsIndexedByPromoCodeId as $promoCodeId => $quantifiedItemsDiscounts) {
            foreach ($quantifiedItemsDiscounts as $itemId => $quantifiedItemDiscount) {
                $totalPrice = $quantifiedItemsPrices[$itemId]->getTotalPrice();
                /** @var \App\Model\Product\Pricing\ProductPrice $productPrice */
                $productPrice = $quantifiedItemsPrices[$itemId]->getUnitPrice();
                $product = $this->productFacade->getById($productPrice->getProductId());

                $subtractAmount = $quantifiedItemDiscount === null || $product->isPromoDiscountDisabled() || $product->isInAnySaleStock() ? Price::zero() : $quantifiedItemDiscount;
                $quantifiedItemsPricesMinusAlreadyAppliedDiscounts[$itemId] = new QuantifiedItemPrice(
                    $quantifiedItemsPrices[$itemId]->getUnitPrice(),
                    $totalPrice->subtract($subtractAmount),
                    $quantifiedItemsPrices[$itemId]->getVat()
                );
            }
        }

        return $quantifiedItemsPricesMinusAlreadyAppliedDiscounts;
    }
}
