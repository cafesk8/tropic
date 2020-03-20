<?php

declare(strict_types=1);

namespace App\Model\Order\Preview;

use App\Model\Order\PromoCode\PromoCode;
use App\Model\Order\PromoCode\PromoCodeData;
use InvalidArgumentException;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser;
use Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice;
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
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Transport\Transport;
use Shopsys\FrameworkBundle\Model\Transport\TransportPriceCalculation;

/**
 * @property \App\Model\Payment\PaymentPriceCalculation $paymentPriceCalculation
 * @method \Shopsys\FrameworkBundle\Model\Pricing\Price|null calculateRoundingPrice(\App\Model\Payment\Payment $payment, \App\Model\Pricing\Currency\Currency $currency, \Shopsys\FrameworkBundle\Model\Pricing\Price $productsPrice, \Shopsys\FrameworkBundle\Model\Pricing\Price|null $transportPrice, \Shopsys\FrameworkBundle\Model\Pricing\Price|null $paymentPrice)
 */
class OrderPreviewCalculation extends BaseOrderPreviewCalculation
{
    /**
     * @var \App\Model\Product\Pricing\QuantifiedProductDiscountCalculation
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
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\QuantifiedProductPriceCalculation $quantifiedProductPriceCalculation
     * @param \App\Model\Product\Pricing\QuantifiedProductDiscountCalculation $quantifiedProductDiscountCalculation
     * @param \Shopsys\FrameworkBundle\Model\Transport\TransportPriceCalculation $transportPriceCalculation
     * @param \App\Model\Payment\PaymentPriceCalculation $paymentPriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderPriceCalculation $orderPriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Pricing\PriceCalculation $priceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Vat\VatFacade $vatFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(
        QuantifiedProductPriceCalculation $quantifiedProductPriceCalculation,
        QuantifiedProductDiscountCalculation $quantifiedProductDiscountCalculation,
        TransportPriceCalculation $transportPriceCalculation,
        PaymentPriceCalculation $paymentPriceCalculation,
        OrderPriceCalculation $orderPriceCalculation,
        PriceCalculation $priceCalculation,
        VatFacade $vatFacade,
        Domain $domain
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
        $this->domain = $domain;
    }

    /**
     * @param \App\Model\Pricing\Currency\Currency $currency
     * @param int $domainId
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedProduct[] $quantifiedProducts
     * @param \App\Model\Transport\Transport|null $transport
     * @param \App\Model\Payment\Payment|null $payment
     * @param \App\Model\Customer\User\CustomerUser|null $customerUser
     * @param string|null $promoCodeDiscountPercent
     * @param \App\Model\Order\PromoCode\PromoCode|null $promoCode
     * @param \App\Model\Cart\Item\CartItem[]|null $giftsInCart
     * @param \App\Model\Cart\Item\CartItem[]|null $promoProductsInCart
     * @param \App\Model\Order\PromoCode\PromoCode[] $promoCodes
     * @param \App\Model\Product\Product|null $orderGiftProduct
     * @return \App\Model\Order\Preview\OrderPreview
     */
    public function calculatePreview(
        Currency $currency,
        int $domainId,
        array $quantifiedProducts,
        ?Transport $transport = null,
        ?Payment $payment = null,
        ?CustomerUser $customerUser = null,
        ?string $promoCodeDiscountPercent = null,
        ?PromoCode $promoCode = null,
        ?array $giftsInCart = [],
        ?array $promoProductsInCart = [],
        array $promoCodes = [],
        ?Product $orderGiftProduct = null
    ): BaseOrderPreview {
        if ($promoCodeDiscountPercent !== null || $promoCode !== null) {
            throw new InvalidArgumentException('Neither "$promoCodeDiscountPercent" nor "$promoCode" argument is supported, you need to use "$promoCodes" array instead');
        }

        $quantifiedItemsPrices = $this->quantifiedProductPriceCalculation->calculatePrices(
            $quantifiedProducts,
            $domainId,
            $customerUser
        );

        $quantifiedItemsDiscountsIndexedByPromoCodeId = $this->getQuantifiedItemsDiscountsIndexedByPromoCodeId($quantifiedItemsPrices, $promoCodes, $currency);

        $productsPrice = $this->getProductsPriceAffectedByMultiplePromoCodes($quantifiedItemsPrices, $quantifiedItemsDiscountsIndexedByPromoCodeId);
        $totalGiftPrice = $this->getTotalGiftsPrice($giftsInCart);
        $totalPromoProductPrice = $this->getTotalPromoProductsPrice($promoProductsInCart);
        $productsPrice = $productsPrice->add($totalPromoProductPrice);
        $productsPrice = $productsPrice->add($totalGiftPrice);
        $transportPrice = $this->getTransportPrice($transport, $currency, $productsPrice, $domainId);
        $paymentPrice = $this->getPaymentPrice($payment, $currency, $productsPrice, $domainId);
        $roundingPrice = $this->getRoundingPrice($payment, $currency, $productsPrice, $paymentPrice, $transportPrice);
        $totalDiscount = $this->calculateTotalDiscount($quantifiedItemsDiscountsIndexedByPromoCodeId, $promoCodes);
        $totalPriceWithoutGiftCertificate = $this->calculateTotalPrice($productsPrice, $transportPrice, $paymentPrice, $roundingPrice);

        $totalPrice = $this->getTotalPrice($totalPriceWithoutGiftCertificate, $promoCodes);

        $orderPreview = new OrderPreview(
            $quantifiedProducts,
            $quantifiedItemsPrices,
            $productsPrice,
            $totalPrice,
            $transport,
            $transportPrice,
            $payment,
            $paymentPrice,
            $roundingPrice,
            $totalPriceWithoutGiftCertificate,
            $giftsInCart,
            $promoProductsInCart,
            $quantifiedItemsDiscountsIndexedByPromoCodeId,
            $orderGiftProduct
        );
        $orderPreview->setPromoCodes($promoCodes);
        $orderPreview->setTotalDiscount($totalDiscount);

        return $orderPreview;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice[] $quantifiedItemsPrices
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price[][]|mixed[][] $quantifiedItemsDiscountsIndexedByPromoCodeId
     * @return \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice[]
     */
    protected function getQuantifiedItemsPricesMinusAlreadyAppliedDiscounts(array $quantifiedItemsPrices, array $quantifiedItemsDiscountsIndexedByPromoCodeId)
    {
        if (empty($quantifiedItemsDiscountsIndexedByPromoCodeId)) {
            return $quantifiedItemsPrices;
        }

        $quantifiedItemsPricesMinusAlreadyAppliedDiscounts = [];
        foreach ($quantifiedItemsDiscountsIndexedByPromoCodeId as $promoCodeId => $quantifiedItemsDiscounts) {
            foreach ($quantifiedItemsDiscounts as $itemId => $quantifiedItemDiscount) {
                $totalPrice = $quantifiedItemsPrices[$itemId]->getTotalPrice();
                $subtractAmount = $quantifiedItemDiscount === null ? Price::zero() : $quantifiedItemDiscount;
                $quantifiedItemsPricesMinusAlreadyAppliedDiscounts[$itemId] = new QuantifiedItemPrice(
                    $quantifiedItemsPrices[$itemId]->getUnitPrice(),
                    $totalPrice->subtract($subtractAmount),
                    $quantifiedItemsPrices[$itemId]->getVat()
                );
            }
        }

        return $quantifiedItemsPricesMinusAlreadyAppliedDiscounts;
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

        /** @var \App\Model\Cart\Item\CartItem $giftInCart */
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

        /** @var \App\Model\Cart\Item\CartItem $promoProductInCart */
        foreach ($promoProductsInCart as $promoProductInCart) {
            $promoProductPrice = $promoProductInCart->getWatchedPrice()->multiply($promoProductInCart->getQuantity());
            $totalPromoProductsPrice = $totalPromoProductsPrice->add(new Price($promoProductPrice, $promoProductPrice));
        }

        return $totalPromoProductsPrice;
    }

    /**
     * @param \App\Model\Transport\Transport $transport
     * @param \App\Model\Pricing\Currency\Currency $currency
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
     * @param \App\Model\Payment\Payment $payment
     * @param \App\Model\Pricing\Currency\Currency $currency
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
     * @param \App\Model\Payment\Payment $payment
     * @param \App\Model\Pricing\Currency\Currency $currency
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
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price[][] $quantifiedItemsDiscountsIndexedByPromoCodeId
     * @param \App\Model\Order\PromoCode\PromoCode[] $promoCodes
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price
     */
    protected function calculateTotalDiscount(array $quantifiedItemsDiscountsIndexedByPromoCodeId, array $promoCodes): Price
    {
        $totalDiscount = Price::zero();
        foreach ($promoCodes as $promoCode) {
            if ($promoCode->getType() === PromoCodeData::TYPE_CERTIFICATE) {
                $totalDiscount->add(new Price($promoCode->getCertificateValue(), $promoCode->getCertificateValue()));
            } else {
                $totalDiscountForPromoCode = $this->calculateTotalDiscountForPromoCode($quantifiedItemsDiscountsIndexedByPromoCodeId, $promoCode);

                $totalDiscount->add($totalDiscountForPromoCode);
            }
        }

        return $totalDiscount;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice[] $quantifiedItemsPrices
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price[][] $quantifiedItemsDiscountsIndexedByPromoCodeId
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price
     */
    protected function getProductsPriceAffectedByMultiplePromoCodes(array $quantifiedItemsPrices, array $quantifiedItemsDiscountsIndexedByPromoCodeId): Price
    {
        $finalPrice = Price::zero();

        foreach ($quantifiedItemsPrices as $quantifiedItemPrice) {
            $finalPrice = $finalPrice->add($quantifiedItemPrice->getTotalPrice());
        }

        foreach ($quantifiedItemsDiscountsIndexedByPromoCodeId as $promCodeId => $quantifiedItemsDiscounts) {
            foreach ($quantifiedItemsDiscounts as $quantifiedItemDiscount) {
                if ($quantifiedItemDiscount !== null) {
                    $finalPrice = $finalPrice->subtract($quantifiedItemDiscount);
                }
            }
        }

        return $finalPrice;
    }

    /**
     * @param array $quantifiedItemsDiscountsIndexedByPromoCodeId
     * @param \App\Model\Order\PromoCode\PromoCode $promoCode
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price
     */
    protected function calculateTotalDiscountForPromoCode(array $quantifiedItemsDiscountsIndexedByPromoCodeId, PromoCode $promoCode): Price
    {
        return array_reduce($quantifiedItemsDiscountsIndexedByPromoCodeId[$promoCode->getId()], function ($totalDiscount, $quantifiedItemsDiscount) {
            if ($quantifiedItemsDiscount === null) {
                return $totalDiscount;
            }

            return $totalDiscount->add($quantifiedItemsDiscount);
        }, Price::zero());
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice[] $quantifiedItemsPrices
     * @param \App\Model\Order\PromoCode\PromoCode[] $promoCodes
     * @param \App\Model\Pricing\Currency\Currency $currency
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price[][]
     */
    protected function getQuantifiedItemsDiscountsIndexedByPromoCodeId(array $quantifiedItemsPrices, array $promoCodes, Currency $currency): array
    {
        $quantifiedItemsDiscountsIndexedByPromoCodeId = [];
        $quantifiedItemsPricesForDiscountsCalculation = $quantifiedItemsPrices;
        foreach ($promoCodes as $promoCode) {
            $quantifiedItemsPricesForDiscountsCalculation = $this->getQuantifiedItemsPricesMinusAlreadyAppliedDiscounts(
                $quantifiedItemsPricesForDiscountsCalculation,
                $quantifiedItemsDiscountsIndexedByPromoCodeId
            );
            $quantifiedItemsDiscountsIndexedByPromoCodeId[$promoCode->getId()] = $this->quantifiedProductDiscountCalculation->calculateDiscountsRoundedByCurrency(
                $quantifiedItemsPricesForDiscountsCalculation,
                $promoCode->getPercent(),
                $currency,
                $promoCode
            );
        }

        return $quantifiedItemsDiscountsIndexedByPromoCodeId;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $totalPriceWithoutGiftCertificate
     * @param \App\Model\Order\PromoCode\PromoCode[] $promoCodes
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price
     */
    protected function getTotalPrice(Price $totalPriceWithoutGiftCertificate, array $promoCodes): Price
    {
        $certificatesPrice = Price::zero();
        foreach ($promoCodes as $promoCode) {
            if ($promoCode->getType() === PromoCodeData::TYPE_CERTIFICATE) {
                $certificatesTotalPriceWithVat = $promoCode->getCertificateValue();
                $certificatedTotalVatAmount = $this->priceCalculation->getVatAmountByPriceWithVat($certificatesTotalPriceWithVat, $this->vatFacade->getDefaultVatForDomain($this->domain->getId()));
                $certificatesTotalPriceWithoutVat = $certificatesTotalPriceWithVat->subtract($certificatedTotalVatAmount);

                $certificatesPrice = $certificatesPrice->add(new Price($certificatesTotalPriceWithoutVat, $certificatesTotalPriceWithVat));
            }
        }

        $totalPrice = $totalPriceWithoutGiftCertificate->subtract($certificatesPrice);
        if ($totalPrice->getPriceWithVat()->isNegative()) {
            $totalPrice = Price::zero();
        }

        return $totalPrice;
    }
}
