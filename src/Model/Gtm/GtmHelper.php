<?php

declare(strict_types=1);

namespace App\Model\Gtm;

use App\Model\Order\Item\OrderItem;
use App\Model\Order\OrderData;
use App\Model\Order\PromoCode\PromoCode;
use App\Model\Order\PromoCode\PromoCodeData;
use App\Twig\NumberFormatterExtension;
use Shopsys\FrameworkBundle\Twig\PriceExtension;

class GtmHelper
{
    private GtmContainer $gtmContainer;

    private PriceExtension $priceExtension;

    private NumberFormatterExtension $numberFormatterExtension;

    /**
     * @param \App\Model\Gtm\GtmContainer $gtmContainer
     * @param \Shopsys\FrameworkBundle\Twig\PriceExtension $priceExtension
     * @param \App\Twig\NumberFormatterExtension $numberFormatterExtension
     */
    public function __construct(
        GtmContainer $gtmContainer,
        PriceExtension $priceExtension,
        NumberFormatterExtension $numberFormatterExtension
    ) {
        $this->gtmContainer = $gtmContainer;
        $this->priceExtension = $priceExtension;
        $this->numberFormatterExtension = $numberFormatterExtension;
    }

    /**
     * @param \App\Model\Order\Item\OrderItem $orderItem
     * @return string
     */
    public function getGtmAvailabilityByOrderItem(OrderItem $orderItem): string
    {
        if (!$orderItem->isTypeProduct() || $orderItem->getProduct() === null) {
            return '';
        }

        $availability = $orderItem->getProduct()->getCalculatedAvailability();
        $availabilityName = $availability->getName($this->gtmContainer->getDataLayer()->getLocale());

        return mb_strtolower($availabilityName);
    }

    /**
     * @param \App\Model\Order\OrderData $orderData
     * @param \App\Model\Order\PromoCode\PromoCode[] $usedPromoCodes
     */
    public function amendGtmCouponToOrderData(OrderData $orderData, array $usedPromoCodes): void
    {
        foreach ($usedPromoCodes as $usedPromoCode) {
            $orderData->gtmCoupons[] = sprintf(
                '%s|%s',
                $usedPromoCode->getCode(),
                $this->getCouponDiscountDescription($usedPromoCode)
            );
        }
    }

    /**
     * @param \App\Model\Order\PromoCode\PromoCode $usedPromoCode
     * @return string
     */
    private function getCouponDiscountDescription(PromoCode $usedPromoCode): string
    {
        if ($usedPromoCode->getType() === PromoCodeData::TYPE_CERTIFICATE) {
            $couponDiscount = $this->priceExtension->priceFilter($usedPromoCode->getNominalDiscount());
        } elseif ($usedPromoCode->isUseNominalDiscount()) {
            $couponDiscount = $this->priceExtension->priceFilter($usedPromoCode->getNominalDiscount());
        } else {
            $couponDiscount = $this->numberFormatterExtension->formatPercent($usedPromoCode->getPercent());
        }

        return $couponDiscount;
    }
}
