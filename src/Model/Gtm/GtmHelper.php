<?php

declare(strict_types=1);

namespace App\Model\Gtm;

use App\Model\Order\Item\OrderItem;
use App\Model\Order\OrderData;
use App\Model\Order\PromoCode\PromoCode;

class GtmHelper
{
    private GtmContainer $gtmContainer;

    /**
     * @param \App\Model\Gtm\GtmContainer $gtmContainer
     */
    public function __construct(
        GtmContainer $gtmContainer
    ) {
        $this->gtmContainer = $gtmContainer;
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
        $orderData->gtmCoupons = array_map(fn (PromoCode $promoCode) => $promoCode->getCode(), $usedPromoCodes);
    }
}
