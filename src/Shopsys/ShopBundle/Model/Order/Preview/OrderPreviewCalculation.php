<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Preview;

use Shopsys\FrameworkBundle\Model\Customer\User;
use Shopsys\FrameworkBundle\Model\Order\OrderPriceCalculation;
use Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreview as BaseOrderPreview;
use Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreviewCalculation as BaseOrderPreviewCalculation;
use Shopsys\FrameworkBundle\Model\Payment\Payment;
use Shopsys\FrameworkBundle\Model\Payment\PaymentPriceCalculation;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\Currency;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Pricing\PriceCalculation;
use Shopsys\FrameworkBundle\Model\Pricing\Vat\VatFacade;
use Shopsys\FrameworkBundle\Model\Product\Pricing\QuantifiedProductDiscountCalculation;
use Shopsys\FrameworkBundle\Model\Product\Pricing\QuantifiedProductPriceCalculation;
use Shopsys\FrameworkBundle\Model\Transport\Transport;
use Shopsys\FrameworkBundle\Model\Transport\TransportPriceCalculation;
use Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode;
use Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeData;

class OrderPreviewCalculation extends BaseOrderPreviewCalculation
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\Pricing\QuantifiedProductDiscountCalculation
     */
    protected $quantifiedProductDiscountCalculation;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\PriceCalculation
     */
    private $priceCalculation;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Vat\VatFacade
     */
    private $vatFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\QuantifiedProductPriceCalculation $quantifiedProductPriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\QuantifiedProductDiscountCalculation $quantifiedProductDiscountCalculation
     * @param \Shopsys\FrameworkBundle\Model\Transport\TransportPriceCalculation $transportPriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Payment\PaymentPriceCalculation $paymentPriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderPriceCalculation $orderPriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Pricing\PriceCalculation $priceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Vat\VatFacade $vatFacade
     */
    public function __construct(
        QuantifiedProductPriceCalculation $quantifiedProductPriceCalculation,
        QuantifiedProductDiscountCalculation $quantifiedProductDiscountCalculation,
        TransportPriceCalculation $transportPriceCalculation,
        PaymentPriceCalculation $paymentPriceCalculation,
        OrderPriceCalculation $orderPriceCalculation,
        PriceCalculation $priceCalculation,
        VatFacade $vatFacade
    ) {
        parent::__construct(
            $quantifiedProductPriceCalculation,
            $quantifiedProductDiscountCalculation,
            $transportPriceCalculation,
            $paymentPriceCalculation,
            $orderPriceCalculation
        );

        $this->priceCalculation = $priceCalculation;
        $this->vatFacade = $vatFacade;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Currency\Currency $currency
     * @param int $domainId
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedProduct[] $quantifiedProducts
     * @param \Shopsys\FrameworkBundle\Model\Transport\Transport|null $transport
     * @param \Shopsys\FrameworkBundle\Model\Payment\Payment|null $payment
     * @param \Shopsys\FrameworkBundle\Model\Customer\User|null $user
     * @param string|null $promoCodeDiscountPercent
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode|null $promoCode
     * @param \Shopsys\ShopBundle\Model\Cart\Item\CartItem[]|null $giftsInCart
     * @param \Shopsys\ShopBundle\Model\Cart\Item\CartItem[]|null $promoProductsInCart
     * @return \Shopsys\ShopBundle\Model\Order\Preview\OrderPreview
     */
    public function calculatePreview(
        Currency $currency,
        int $domainId,
        array $quantifiedProducts,
        ?Transport $transport = null,
        ?Payment $payment = null,
        ?User $user = null,
        ?string $promoCodeDiscountPercent = null,
        ?PromoCode $promoCode = null,
        ?array $giftsInCart = [],
        ?array $promoProductsInCart = []
    ): BaseOrderPreview {
        $quantifiedItemsPrices = $this->quantifiedProductPriceCalculation->calculatePrices(
            $quantifiedProducts,
            $domainId,
            $user
        );
        $quantifiedItemsDiscounts = $this->quantifiedProductDiscountCalculation->calculateDiscounts(
            $quantifiedItemsPrices,
            $promoCodeDiscountPercent,
            $promoCode
        );

        $productsPrice = $this->getProductsPrice($quantifiedItemsPrices, $quantifiedItemsDiscounts);
        $totalGiftPrice = $this->getTotalGiftsPrice($giftsInCart);
        $totalPromoProductPrice = $this->getTotalPromoProductsPrice($promoProductsInCart);
        $productsPrice = $productsPrice->add($totalPromoProductPrice);
        $productsPrice = $productsPrice->add($totalGiftPrice);
        $transportPrice = $this->getTransportPrice($transport, $currency, $productsPrice, $domainId);
        $paymentPrice = $this->getPaymentPrice($payment, $currency, $productsPrice, $domainId);
        $roundingPrice = $this->getRoundingPrice($payment, $currency, $productsPrice, $paymentPrice, $transportPrice);
        $totalDiscount = $this->calculateTotalDiscount($quantifiedItemsDiscounts, $promoCode);
        $totalPriceWithoutGiftCertificate = $this->calculateTotalPrice($productsPrice, $transportPrice, $paymentPrice, $roundingPrice);

        $totalPrice = $totalPriceWithoutGiftCertificate;

        if ($promoCode !== null && $promoCode->getType() === PromoCodeData::TYPE_CERTIFICATE) {
            $totalPriceWithVat = $promoCode->getCertificateValue();
            $totalVatAmount = $this->priceCalculation->getVatAmountByPriceWithVat($totalPriceWithVat, $this->vatFacade->getDefaultVat());
            $totalPriceWithoutVat = $totalPriceWithVat->subtract($totalVatAmount);

            $certificatePrice = new Price($totalPriceWithoutVat, $totalPriceWithVat);
            $totalPrice = $totalPriceWithoutGiftCertificate->subtract($certificatePrice);
            if ($totalPrice->getPriceWithVat()->isNegative()) {
                $totalPrice = Price::zero();
            }
        }

        $orderPreview = new OrderPreview(
            $quantifiedProducts,
            $quantifiedItemsPrices,
            $quantifiedItemsDiscounts,
            $productsPrice,
            $totalPrice,
            $transport,
            $transportPrice,
            $payment,
            $paymentPrice,
            $roundingPrice,
            $promoCodeDiscountPercent,
            $totalPriceWithoutGiftCertificate,
            $giftsInCart,
            $promoProductsInCart
        );
        $orderPreview->setPromoCode($promoCode);
        $orderPreview->setTotalDiscount($totalDiscount);

        return $orderPreview;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $productsPrice
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $transportPrice
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $paymentPrice
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $roundingPrice
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price
     */
    protected function calculateTotalPrice(
        Price $productsPrice,
        ?Price $transportPrice = null,
        ?Price $paymentPrice = null,
        ?Price $roundingPrice = null
    ): Price {
        $totalPrice = parent::calculateTotalPrice($productsPrice, $transportPrice, $paymentPrice, $roundingPrice);

        return $totalPrice;
    }

    /**
     * @param array|null $giftsInCart
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price
     */
    private function getTotalGiftsPrice(?array $giftsInCart = null): Price
    {
        $totalGiftsPrice = Price::zero();

        if ($giftsInCart === null) {
            return $totalGiftsPrice;
        }

        /** @var \Shopsys\ShopBundle\Model\Cart\Item\CartItem $giftInCart */
        foreach ($giftsInCart as $giftInCart) {
            $giftPrice = $giftInCart->getWatchedPrice()->multiply($giftInCart->getQuantity());
            $totalGiftsPrice = $totalGiftsPrice->add(new Price($giftPrice, $giftPrice));
        }

        return $totalGiftsPrice;
    }

    /**
     * @param array|null $promoProductsInCart
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price
     */
    private function getTotalPromoProductsPrice(?array $promoProductsInCart = null): Price
    {
        $totalPromoProductsPrice = Price::zero();

        if ($promoProductsInCart === null) {
            return $totalPromoProductsPrice;
        }

        /** @var \Shopsys\ShopBundle\Model\Cart\Item\CartItem $promoProductInCart */
        foreach ($promoProductsInCart as $promoProductInCart) {
            $promoProductPrice = $promoProductInCart->getWatchedPrice()->multiply($promoProductInCart->getQuantity());
            $totalPromoProductsPrice = $totalPromoProductsPrice->add(new Price($promoProductPrice, $promoProductPrice));
        }

        return $totalPromoProductsPrice;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Transport\Transport $transport
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Currency\Currency $currency
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $productsPrice
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price|null
     */
    private function getTransportPrice(?Transport $transport, Currency $currency, Price $productsPrice, int $domainId): ?Price
    {
        if ($transport !== null) {
            return $this->transportPriceCalculation->calculatePrice(
                $transport,
                $currency,
                $productsPrice,
                $domainId
            );
        }

        return null;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Payment\Payment $payment
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Currency\Currency $currency
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $productsPrice
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price|null
     */
    private function getPaymentPrice(?Payment $payment, Currency $currency, Price $productsPrice, int $domainId): ?Price
    {
        if ($payment !== null) {
            return $this->paymentPriceCalculation->calculatePrice(
                $payment,
                $currency,
                $productsPrice,
                $domainId
            );
        }

        return null;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Payment\Payment $payment
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Currency\Currency $currency
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $productsPrice
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $paymentPrice
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $transportPrice
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price|null
     */
    private function getRoundingPrice(?Payment $payment, Currency $currency, Price $productsPrice, ?Price $paymentPrice, ?Price $transportPrice): ?Price
    {
        if ($payment !== null) {
            return $this->calculateRoundingPrice(
                $payment,
                $currency,
                $productsPrice,
                $transportPrice,
                $paymentPrice
            );
        }

        return null;
    }

    /**
     * @param array $quantifiedItemsDiscounts
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode|null $promoCode
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price
     */
    protected function calculateTotalDiscount(array $quantifiedItemsDiscounts, ?PromoCode $promoCode): Price
    {
        if ($promoCode !== null && $promoCode->getType() === PromoCodeData::TYPE_CERTIFICATE) {
            return new Price($promoCode->getCertificateValue(), $promoCode->getCertificateValue());
        }

        return array_reduce($quantifiedItemsDiscounts, function ($totalDiscount, $quantifiedItemsDiscount) {
            if ($quantifiedItemsDiscount === null) {
                return $totalDiscount;
            }

            return $totalDiscount->add($quantifiedItemsDiscount);
        }, Price::zero());
    }
}
