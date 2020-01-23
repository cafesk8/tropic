<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Gtm;

use Shopsys\FrameworkBundle\Twig\PriceExtension;
use Shopsys\ShopBundle\Model\Order\Item\OrderItem;
use Shopsys\ShopBundle\Model\Order\OrderData;
use Shopsys\ShopBundle\Model\Order\Preview\OrderPreview;
use Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode;
use Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeData;
use Shopsys\ShopBundle\Model\Product\Product;
use Shopsys\ShopBundle\Model\Product\ProductCachedAttributesFacade;
use Shopsys\ShopBundle\Twig\NumberFormatterExtension;

class GtmHelper
{
    /**
     * @var \Shopsys\ShopBundle\Model\Gtm\GtmContainer
     */
    private $gtmContainer;

    /**
     * @var \Shopsys\FrameworkBundle\Twig\PriceExtension
     */
    private $priceExtension;

    /**
     * @var \Shopsys\ShopBundle\Twig\NumberFormatterExtension
     */
    private $numberFormatterExtension;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductCachedAttributesFacade
     */
    private $productCachedAttributesFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Gtm\GtmContainer $gtmContainer
     * @param \Shopsys\FrameworkBundle\Twig\PriceExtension $priceExtension
     * @param \Shopsys\ShopBundle\Twig\NumberFormatterExtension $numberFormatterExtension
     * @param \Shopsys\ShopBundle\Model\Product\ProductCachedAttributesFacade $productCachedAttributesFacade
     */
    public function __construct(
        GtmContainer $gtmContainer,
        PriceExtension $priceExtension,
        NumberFormatterExtension $numberFormatterExtension,
        ProductCachedAttributesFacade $productCachedAttributesFacade
    ) {
        $this->gtmContainer = $gtmContainer;
        $this->priceExtension = $priceExtension;
        $this->numberFormatterExtension = $numberFormatterExtension;
        $this->productCachedAttributesFacade = $productCachedAttributesFacade;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Item\OrderItem $orderItem
     * @return string
     */
    public function getGtmAvailabilityByOrderItem(OrderItem $orderItem): string
    {
        if (!$orderItem->isTypeProduct() || $orderItem->getProduct() === null) {
            '';
        }

        $availability = $orderItem->getProduct()->getCalculatedAvailability();
        $availabilityName = $availability->getName($this->gtmContainer->getDataLayer()->getLocale());
        $gtmAvailability = mb_strtolower($availabilityName);

        return $gtmAvailability;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\OrderData $orderData
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode[] $usedPromoCodes
     * @param \Shopsys\ShopBundle\Model\Order\Preview\OrderPreview|null $orderPreview
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
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode $usedPromoCode
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
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode $usedPromoCode
     * @param \Shopsys\ShopBundle\Model\Order\Preview\OrderPreview|null $orderPreview
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

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @return string
     */
    public function getVariantByProduct(Product $product): string
    {
        $distinguishingParameterValue = $this->productCachedAttributesFacade->getProductDistinguishingParameterValue($product);
        return sprintf(
            '%s|%s',
            $distinguishingParameterValue->getFirstDistinguishingParameterValue() ?? '',
            $distinguishingParameterValue->getSecondDistinguishingParameterValue() ?? ''
        );
    }
}
