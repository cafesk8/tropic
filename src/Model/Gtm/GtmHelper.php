<?php

declare(strict_types=1);

namespace App\Model\Gtm;

use App\Model\Order\Item\OrderItem;
use App\Model\Order\OrderData;
use App\Model\Order\Preview\OrderPreview;
use App\Model\Order\PromoCode\PromoCode;
use App\Model\Order\PromoCode\PromoCodeData;
use App\Twig\NumberFormatterExtension;
use Shopsys\FrameworkBundle\Twig\PriceExtension;

class GtmHelper
{
    /**
     * @var \App\Model\Gtm\GtmContainer
     */
    private $gtmContainer;

    /**
     * @var \Shopsys\FrameworkBundle\Twig\PriceExtension
     */
    private $priceExtension;

    /**
     * @var \App\Twig\NumberFormatterExtension
     */
    private $numberFormatterExtension;

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
        $gtmAvailability = mb_strtolower($availabilityName);

        return $gtmAvailability;
    }

    /**
     * @param \App\Model\Order\OrderData $orderData
     * @param \App\Model\Order\PromoCode\PromoCode[] $usedPromoCodes
     * @param \App\Model\Order\Preview\OrderPreview|null $orderPreview
     */
    public function amendGtmCouponToOrderData(OrderData $orderData, array $usedPromoCodes, ?OrderPreview $orderPreview = null): void
    {
        foreach ($usedPromoCodes as $usedPromoCode) {
            $orderData->gtmCoupons[] = sprintf(
                '%s|%s|%s',
                $usedPromoCode->getCode(),
                $this->getCouponDiscountDescription($usedPromoCode),
                $this->getPriceWithoutDiscount($usedPromoCode, $orderPreview)
            );
        }

        // free transport can be place here
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

    /**
     * @param \App\Model\Order\PromoCode\PromoCode $usedPromoCode
     * @param \App\Model\Order\Preview\OrderPreview|null $orderPreview
     * @return string
     */
    private function getPriceWithoutDiscount(PromoCode $usedPromoCode, ?OrderPreview $orderPreview): string
    {
        if ($orderPreview === null) {
            return '';
        }

        if ($usedPromoCode->getType() === PromoCodeData::TYPE_CERTIFICATE) {
            $priceWithoutDiscount = $orderPreview->getTotalPrice()->add($orderPreview->getTotalDiscount());
        } elseif ($usedPromoCode->isUseNominalDiscount()) {
            $priceWithoutDiscount = $orderPreview->getProductsPrice()->add($orderPreview->getTotalDiscount());
        } else {
            $priceWithoutDiscount = $orderPreview->getProductsPrice()->add($orderPreview->getTotalDiscount());
        }

        return $this->priceExtension->priceFilter($priceWithoutDiscount->getPriceWithVat());
    }
}
