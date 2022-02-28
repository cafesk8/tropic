<?php

declare(strict_types=1);

namespace App\Model\Order\Discount;

use App\Model\Customer\User\CustomerUser;
use App\Model\Order\PromoCode\PromoCode;
use App\Model\Pricing\Currency\CurrencyFacade;
use App\Model\Product\Pricing\QuantifiedProductDiscountCalculation;
use App\Model\Product\Pricing\QuantifiedProductPriceCalculation;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\Currency;
use Shopsys\FrameworkBundle\Model\Pricing\Price;

class OrderDiscountCalculation
{
    /**
     * @var \App\Model\Order\Discount\OrderDiscountLevelFacade
     */
    private $orderDiscountLevelFacade;

    /**
     * @var \App\Model\Pricing\Currency\CurrencyFacade
     */
    private $currencyFacade;

    /**
     * @var \App\Model\Product\Pricing\QuantifiedProductPriceCalculation
     */
    private $quantifiedProductPriceCalculation;

    /**
     * @var \App\Model\Product\Pricing\QuantifiedProductDiscountCalculation
     */
    private $quantifiedProductDiscountCalculation;

    /**
     * @param \App\Model\Product\Pricing\QuantifiedProductPriceCalculation $quantifiedProductPriceCalculation
     * @param \App\Model\Product\Pricing\QuantifiedProductDiscountCalculation $quantifiedProductDiscountCalculation
     * @param \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     * @param \App\Model\Order\Discount\OrderDiscountLevelFacade $orderDiscountLevelFacade
     */
    public function __construct(
        QuantifiedProductPriceCalculation $quantifiedProductPriceCalculation,
        QuantifiedProductDiscountCalculation $quantifiedProductDiscountCalculation,
        CurrencyFacade $currencyFacade,
        OrderDiscountLevelFacade $orderDiscountLevelFacade
    ) {
        $this->quantifiedProductDiscountCalculation = $quantifiedProductDiscountCalculation;
        $this->quantifiedProductPriceCalculation = $quantifiedProductPriceCalculation;
        $this->currencyFacade = $currencyFacade;
        $this->orderDiscountLevelFacade = $orderDiscountLevelFacade;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedProduct[] $quantifiedProducts
     * @param \App\Model\Order\PromoCode\PromoCode $promoCode
     * @param int $domainId
     * @param \App\Model\Customer\User\CustomerUser|null $customerUser
     * @param int $activeOrderDiscountLevelId
     * @return bool
     */
    public function isDiscountByPromoCodeBetterThanDiscountByOrderDiscountLevel(
        array $quantifiedProducts,
        PromoCode $promoCode,
        int $domainId,
        ?CustomerUser $customerUser,
        int $activeOrderDiscountLevelId
    ): bool {
        $quantifiedItemsPrices = $this->quantifiedProductPriceCalculation->calculatePrices(
            $quantifiedProducts,
            $domainId,
            $customerUser,
            false
        );
        $currency = $this->currencyFacade->getDomainDefaultCurrencyByDomainId($domainId);
        $quantifiedItemsDiscountsIndexedByPromoCodeId = $this->quantifiedProductDiscountCalculation->getQuantifiedItemsDiscountsIndexedByPromoCodeId($quantifiedItemsPrices, [$promoCode], $currency);

        $discountByPromoCode = $this->calculateTotalDiscountForPromoCode($quantifiedItemsDiscountsIndexedByPromoCodeId, $promoCode);

        $discountByOrderDiscountLevel = $this->calculateDiscountByOrderDiscountLevel($activeOrderDiscountLevelId, $quantifiedItemsPrices, $currency, $domainId);

        $discountByPromoCodePriceWithVat = $discountByPromoCode->getPriceWithVat();
        $discountByOrderDiscountLevelPriceWithVat = $discountByOrderDiscountLevel->getPriceWithVat();

        return $discountByPromoCodePriceWithVat->isGreaterThan($discountByOrderDiscountLevelPriceWithVat);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price[] $quantifiedItemsDiscountsByIndex
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price
     */
    public function calculateOrderDiscountLevelTotalDiscount(array $quantifiedItemsDiscountsByIndex): Price
    {
        $totalDiscount = Price::zero();
        foreach ($quantifiedItemsDiscountsByIndex as $quantifiedItemDiscount) {
            if ($quantifiedItemDiscount !== null) {
                $totalDiscount = $totalDiscount->add($quantifiedItemDiscount);
            }
        }

        return $totalDiscount;
    }

    /**
     * @param int $activeOrderDiscountLevelId
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice[] $quantifiedItemsPrices
     * @param \App\Model\Pricing\Currency\Currency $currency
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price
     */
    private function calculateDiscountByOrderDiscountLevel(
        int $activeOrderDiscountLevelId,
        array $quantifiedItemsPrices,
        Currency $currency,
        int $domainId
    ): Price {
        $activeOrderDiscountLevel = $this->orderDiscountLevelFacade->findById($activeOrderDiscountLevelId);
        $quantifiedItemsDiscountsByIndex = $this->quantifiedProductDiscountCalculation->calculateQuantifiedItemsDiscountsRoundedByCurrency(
            $quantifiedItemsPrices,
            $currency,
            $activeOrderDiscountLevel,
            $domainId
        );
        $totalDiscount = Price::zero();
        foreach ($quantifiedItemsDiscountsByIndex as $itemDiscount) {
            $totalDiscount = $totalDiscount->add(new Price($itemDiscount->getPriceWithoutVat(), $itemDiscount->getPriceWithVat()));
        }

        return $totalDiscount;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price[][] $quantifiedItemsDiscountsIndexedByPromoCodeId
     * @param \App\Model\Order\PromoCode\PromoCode $promoCode
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price
     */
    private function calculateTotalDiscountForPromoCode(array $quantifiedItemsDiscountsIndexedByPromoCodeId, PromoCode $promoCode): Price
    {
        return array_reduce($quantifiedItemsDiscountsIndexedByPromoCodeId[$promoCode->getId()], function ($totalDiscount, $quantifiedItemsDiscount) {
            if ($quantifiedItemsDiscount === null) {
                return $totalDiscount;
            }

            return $totalDiscount->add($quantifiedItemsDiscount);
        }, Price::zero());
    }
}
