<?php

declare(strict_types=1);

namespace App\Model\Order\Preview;

use App\Component\FlashMessage\FlashMessageSender;
use App\Model\Order\Discount\CurrentOrderDiscountLevelFacade;
use App\Model\Order\Discount\OrderDiscountCalculation;
use App\Model\Order\Discount\OrderDiscountLevel;
use App\Model\Order\Discount\OrderDiscountLevelFacade;
use App\Model\Order\PromoCode\CurrentPromoCodeFacade;
use App\Model\Order\PromoCode\PromoCode;
use InvalidArgumentException;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser;
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
 * @property \App\Model\Product\Pricing\QuantifiedProductPriceCalculation $quantifiedProductPriceCalculation
 * @method \Shopsys\FrameworkBundle\Model\Pricing\Price|null calculateRoundingPrice(\App\Model\Payment\Payment $payment, \App\Model\Pricing\Currency\Currency $currency, \Shopsys\FrameworkBundle\Model\Pricing\Price $productsPrice, \Shopsys\FrameworkBundle\Model\Pricing\Price|null $transportPrice, \Shopsys\FrameworkBundle\Model\Pricing\Price|null $paymentPrice)
 * @property \App\Model\Transport\TransportPriceCalculation $transportPriceCalculation
 */
class OrderPreviewCalculation extends BaseOrderPreviewCalculation
{
    /**
     * @var \App\Model\Product\Pricing\QuantifiedProductDiscountCalculation
     */
    protected $quantifiedProductDiscountCalculation;

    /**
     * @var \App\Model\Order\Discount\OrderDiscountLevelFacade
     */
    private $orderDiscountLevelFacade;

    /**
     * @var \App\Model\Order\PromoCode\CurrentPromoCodeFacade
     */
    private $currentPromoCodeFacade;

    /**
     * @var \App\Model\Order\Discount\CurrentOrderDiscountLevelFacade
     */
    private $currentOrderDiscountLevelFacade;

    /**
     * @var \App\Model\Order\Discount\OrderDiscountCalculation
     */
    private $orderDiscountCalculation;

    /**
     * @var \App\Component\FlashMessage\FlashMessageSender
     */
    private $flashMessageSender;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\PriceCalculation
     */
    private $priceCalculation;

    /**
     * @var \App\Model\Pricing\Vat\VatFacade
     */
    private $vatFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \App\Model\Product\Pricing\QuantifiedProductPriceCalculation $quantifiedProductPriceCalculation
     * @param \App\Model\Product\Pricing\QuantifiedProductDiscountCalculation $quantifiedProductDiscountCalculation
     * @param \App\Model\Transport\TransportPriceCalculation $transportPriceCalculation
     * @param \App\Model\Payment\PaymentPriceCalculation $paymentPriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderPriceCalculation $orderPriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Pricing\PriceCalculation $priceCalculation
     * @param \App\Model\Pricing\Vat\VatFacade $vatFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Order\Discount\OrderDiscountLevelFacade $orderDiscountLevelFacade
     * @param \App\Model\Order\PromoCode\CurrentPromoCodeFacade $currentPromoCodeFacade
     * @param \App\Model\Order\Discount\CurrentOrderDiscountLevelFacade $currentOrderDiscountLevelFacade
     * @param \App\Model\Order\Discount\OrderDiscountCalculation $orderDiscountCalculation
     * @param \App\Component\FlashMessage\FlashMessageSender $flashMessageSender
     */
    public function __construct(
        QuantifiedProductPriceCalculation $quantifiedProductPriceCalculation,
        QuantifiedProductDiscountCalculation $quantifiedProductDiscountCalculation,
        TransportPriceCalculation $transportPriceCalculation,
        PaymentPriceCalculation $paymentPriceCalculation,
        OrderPriceCalculation $orderPriceCalculation,
        PriceCalculation $priceCalculation,
        VatFacade $vatFacade,
        Domain $domain,
        OrderDiscountLevelFacade $orderDiscountLevelFacade,
        CurrentPromoCodeFacade $currentPromoCodeFacade,
        CurrentOrderDiscountLevelFacade $currentOrderDiscountLevelFacade,
        OrderDiscountCalculation $orderDiscountCalculation,
        FlashMessageSender $flashMessageSender
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
        $this->orderDiscountLevelFacade = $orderDiscountLevelFacade;
        $this->currentPromoCodeFacade = $currentPromoCodeFacade;
        $this->currentOrderDiscountLevelFacade = $currentOrderDiscountLevelFacade;
        $this->orderDiscountCalculation = $orderDiscountCalculation;
        $this->flashMessageSender = $flashMessageSender;
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
     * @param \App\Model\Order\PromoCode\PromoCode[] $promoCodes
     * @param \App\Model\Product\Product|null $orderGiftProduct
     * @param bool $simulateRegistration
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
        array $promoCodes = [],
        ?Product $orderGiftProduct = null,
        bool $simulateRegistration = false
    ): BaseOrderPreview {
        if ($promoCodeDiscountPercent !== null || $promoCode !== null) {
            throw new InvalidArgumentException('Neither "$promoCodeDiscountPercent" nor "$promoCode" argument is supported, you need to use "$promoCodes" array instead');
        }

        $quantifiedItemsPrices = $this->quantifiedProductPriceCalculation->calculatePrices(
            $quantifiedProducts,
            $domainId,
            $customerUser,
            $simulateRegistration
        );
        $productsPriceWithoutDiscounts = $this->getProductsPriceWithoutDiscounts($quantifiedItemsPrices);
        $defaultProductsPriceWithoutDiscounts = $productsPriceWithoutDiscounts;
        if ($customerUser !== null || $simulateRegistration === true) {
            $defaultQuantifiedItemsPrices = $this->quantifiedProductPriceCalculation->calculatePrices(
                $quantifiedProducts,
                $domainId,
                null,
                false
            );
            $defaultProductsPriceWithoutDiscounts = $this->getProductsPriceWithoutDiscounts($defaultQuantifiedItemsPrices);
        }
        $productsPrice = $productsPriceWithoutDiscounts;
        $quantifiedItemsDiscountsByIndex = [];
        $quantifiedItemsDiscountsIndexedByPromoCodeId = $this->quantifiedProductDiscountCalculation->getQuantifiedItemsDiscountsIndexedByPromoCodeId($quantifiedItemsPrices, $promoCodes, $currency);

        $matchingOrderDiscountLevel = $this->orderDiscountLevelFacade->findMatchingLevel($domainId, $productsPriceWithoutDiscounts->getPriceWithVat());
        $promoCodeTypePromoCode = $this->findPromoCodeTypePromoCode($promoCodes);

        $existsOrderDiscountLevel = $matchingOrderDiscountLevel !== null;
        $existsPromoCodeDiscount = $promoCodeTypePromoCode !== null;

        if ($existsPromoCodeDiscount || $existsOrderDiscountLevel) {
            $existBothPromoCodeAndOrderDiscountLevelDiscounts = $existsPromoCodeDiscount && $existsOrderDiscountLevel;
            $isDiscountByPromoCodeBetterThanDiscountByOrderDiscountLevel = false;
            if ($existBothPromoCodeAndOrderDiscountLevelDiscounts) {
                $isDiscountByPromoCodeBetterThanDiscountByOrderDiscountLevel = $this->orderDiscountCalculation->isDiscountByPromoCodeBetterThanDiscountByOrderDiscountLevel(
                    $quantifiedProducts,
                    $promoCodeTypePromoCode,
                    $domainId,
                    $customerUser,
                    $matchingOrderDiscountLevel->getId()
                );
            }
            if ($existsPromoCodeDiscount && !$existsOrderDiscountLevel || $existBothPromoCodeAndOrderDiscountLevelDiscounts && $isDiscountByPromoCodeBetterThanDiscountByOrderDiscountLevel) {
                $matchingOrderDiscountLevel = null;
                if ($this->currentOrderDiscountLevelFacade->getActiveOrderLevelDiscountId() !== null) {
                    $this->currentOrderDiscountLevelFacade->unsetActiveOrderLevelDiscount();
                    $this->flashMessageSender->addInfoFlash(t('Automatick?? sleva na cel?? n??kup byla deaktivov??na, proto??e sleva z??skan?? d??ky kuponu je pro v??s v??hodn??j????.'));
                }
                $productsPrice = $this->getProductsPriceAffectedByMultiplePromoCodes(
                    $productsPriceWithoutDiscounts,
                    $quantifiedItemsDiscountsIndexedByPromoCodeId
                );
            }
            if ($existsOrderDiscountLevel && !$existsPromoCodeDiscount || $existBothPromoCodeAndOrderDiscountLevelDiscounts && !$isDiscountByPromoCodeBetterThanDiscountByOrderDiscountLevel) {
                $quantifiedItemsDiscountsByIndex = $this->quantifiedProductDiscountCalculation->calculateQuantifiedItemsDiscountsRoundedByCurrency(
                    $quantifiedItemsPrices,
                    $currency,
                    $matchingOrderDiscountLevel,
                    $domainId
                );
                if (!empty($quantifiedItemsDiscountsByIndex)) {
                    if ($simulateRegistration === false) {
                        $promoCodes = $this->removeAllPromoCodesThatAreNotGiftCertificatesAndActivateOrderDiscountLevel($matchingOrderDiscountLevel, $promoCodes);
                    }
                    $productsPrice = $this->getProductsPriceAffectedByOrderDiscountLevel(
                        $productsPriceWithoutDiscounts,
                        $quantifiedItemsDiscountsByIndex
                    );
                } elseif ($this->currentOrderDiscountLevelFacade->getActiveOrderLevelDiscountId() !== null) {
                    $matchingOrderDiscountLevel = null;
                    $this->currentOrderDiscountLevelFacade->unsetActiveOrderLevelDiscount();
                    $this->flashMessageSender->addInfoFlash(t('Automatick?? sleva na cel?? n??kup byla deaktivov??na, proto??e ji nelze aplikovat na ????dn?? produkt v ko????ku.'));
                } else {
                    $matchingOrderDiscountLevel = null;
                }
            }
        }

        $totalGiftPrice = $this->getTotalGiftsPrice($giftsInCart);
        $productsPrice = $productsPrice->add($totalGiftPrice);
        $transportPrice = $this->getTransportPrice($transport, $currency, $productsPrice, $domainId);
        $transportFee = $this->calculateTransportFee($quantifiedProducts, $domainId);
        $paymentPrice = $this->getPaymentPrice($payment, $currency, $productsPrice, $domainId);
        $roundingPrice = $this->getRoundingPrice($payment, $currency, $productsPrice, $paymentPrice, $transportPrice);
        $orderDiscountLevelTotalDiscount = $this->orderDiscountCalculation->calculateOrderDiscountLevelTotalDiscount($quantifiedItemsDiscountsByIndex);
        $totalPriceWithoutGiftCertificate = $this->calculateTotalPrice($productsPrice, $transportPrice, $paymentPrice, $roundingPrice, $transportFee);

        $totalPrice = $this->getTotalPriceAffectedByGiftCertificates($totalPriceWithoutGiftCertificate, $promoCodes);

        $orderPreview = new OrderPreview(
            $quantifiedProducts,
            $quantifiedItemsPrices,
            $quantifiedItemsDiscountsByIndex,
            $productsPrice,
            $totalPrice,
            $productsPriceWithoutDiscounts,
            $defaultProductsPriceWithoutDiscounts,
            $transport,
            $transportPrice,
            $transportFee,
            $payment,
            $paymentPrice,
            $roundingPrice,
            $totalPriceWithoutGiftCertificate,
            $giftsInCart,
            $quantifiedItemsDiscountsIndexedByPromoCodeId,
            $orderGiftProduct,
            $matchingOrderDiscountLevel,
            $simulateRegistration
        );
        $orderPreview->setPromoCodes($promoCodes);
        $orderPreview->setOrderDiscountLevelTotalDiscount($orderDiscountLevelTotalDiscount);

        return $orderPreview;
    }

    /**
     * @param \App\Model\Order\PromoCode\PromoCode[] $promoCodes
     * @return \App\Model\Order\PromoCode\PromoCode|null
     */
    private function findPromoCodeTypePromoCode(array $promoCodes): ?PromoCode
    {
        foreach ($promoCodes as $promoCode) {
            if ($promoCode->isTypePromoCode()) {
                return $promoCode;
            }
        }

        return null;
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
     * @param \App\Model\Transport\Transport|null $transport
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
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $productsPriceWithoutDiscounts
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price[][] $quantifiedItemsDiscountsIndexedByPromoCodeId
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price
     */
    private function getProductsPriceAffectedByMultiplePromoCodes(
        Price $productsPriceWithoutDiscounts,
        array $quantifiedItemsDiscountsIndexedByPromoCodeId
    ): Price {
        $productsPrice = $productsPriceWithoutDiscounts;

        foreach ($quantifiedItemsDiscountsIndexedByPromoCodeId as $promCodeId => $quantifiedItemsDiscounts) {
            foreach ($quantifiedItemsDiscounts as $quantifiedItemDiscount) {
                if ($quantifiedItemDiscount !== null) {
                    $productsPrice = $productsPrice->subtract($quantifiedItemDiscount);
                }
            }
        }

        return $productsPrice;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $productsPriceWithoutDiscounts
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price[] $quantifiedItemsDiscountsByIndex
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price
     */
    private function getProductsPriceAffectedByOrderDiscountLevel(
        Price $productsPriceWithoutDiscounts,
        array $quantifiedItemsDiscountsByIndex
    ): Price {
        $productsPrice = $productsPriceWithoutDiscounts;
        foreach ($quantifiedItemsDiscountsByIndex as $quantifiedItemDiscount) {
            if ($quantifiedItemDiscount !== null) {
                $productsPrice = $productsPrice->subtract($quantifiedItemDiscount);
            }
        }

        return $productsPrice;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $totalPriceWithoutGiftCertificate
     * @param \App\Model\Order\PromoCode\PromoCode[] $promoCodes
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price
     */
    protected function getTotalPriceAffectedByGiftCertificates(Price $totalPriceWithoutGiftCertificate, array $promoCodes): Price
    {
        $certificatesPrice = Price::zero();
        foreach ($promoCodes as $promoCode) {
            if ($promoCode->isTypeGiftCertificate()) {
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

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice[] $quantifiedItemsPrices
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price
     */
    private function getProductsPriceWithoutDiscounts(array $quantifiedItemsPrices): Price
    {
        $productsPrice = Price::zero();

        foreach ($quantifiedItemsPrices as $quantifiedItemPrice) {
            $productsPrice = $productsPrice->add($quantifiedItemPrice->getTotalPrice());
        }

        return $productsPrice;
    }

    /**
     * @param \App\Model\Order\Discount\OrderDiscountLevel $activeOrderDiscountLevel
     * @param \App\Model\Order\PromoCode\PromoCode[] $promoCodes
     * @return \App\Model\Order\PromoCode\PromoCode[]
     */
    private function removeAllPromoCodesThatAreNotGiftCertificatesAndActivateOrderDiscountLevel(OrderDiscountLevel $activeOrderDiscountLevel, array $promoCodes): array
    {
        $removedPromoCodesCount = $this->currentPromoCodeFacade->removeAllEnteredPromoCodesThatAreNotGiftCertificates();
        if ($removedPromoCodesCount > 0) {
            $this->flashMessageSender->addSuccessFlash(t('Byla aktivov??na automatick?? sleva na cel?? n??kup ve v????i %percent% %, kter?? je pro v??s v??hodn??j???? ne?? pou??it?? slevov?? kup??n, tak??e byl kup??n z ko????ku odstran??n.', ['%percent%' => $activeOrderDiscountLevel->getDiscountPercent()]));
        } elseif ($activeOrderDiscountLevel->getId() !== $this->currentOrderDiscountLevelFacade->getActiveOrderLevelDiscountId()) {
            $this->flashMessageSender->addSuccessFlash(t('Byla aktivov??na automatick?? sleva na cel?? n??kup ve v????i %percent% %.', ['%percent%' => $activeOrderDiscountLevel->getDiscountPercent()]));
        }
        $this->currentOrderDiscountLevelFacade->setActiveOrderLevelDiscountId($activeOrderDiscountLevel->getId());
        $promoCodes = array_filter($promoCodes, function (PromoCode $promoCode) {
            return $promoCode->isTypeGiftCertificate();
        });

        return $promoCodes;
    }

    /**
     * @param \App\Model\Order\Item\QuantifiedProduct[] $quantifiedProducts
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price|null
     */
    private function calculateTransportFee(array $quantifiedProducts, int $domainId): ?Price
    {
        $feeWithVat = Money::zero();

        foreach ($quantifiedProducts as $quantifiedProduct) {
            $product = $quantifiedProduct->getProduct();

            if ($product->hasTransportFee($domainId)) {
                $currentFee = $product->getTransportFee($domainId)->multiply(
                    (string)ceil(
                        $quantifiedProduct->getQuantity() / $product->getTransportFeeMultiplier()
                    )
                );

                if ($currentFee->isGreaterThan($feeWithVat)) {
                    $feeWithVat = $currentFee;
                }
            }
        }

        if ($feeWithVat->isZero()) {
            return null;
        }

        $vat = $this->vatFacade->getDefaultVatForDomain($domainId);
        $feeWithoutVat = $feeWithVat->divide((string)(1 + $vat->getPercent() / 100), 2);

        return new Price($feeWithoutVat, $feeWithVat);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $productsPrice
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $transportPrice
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $paymentPrice
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $roundingPrice
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $transportFee
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price
     */
    protected function calculateTotalPrice(Price $productsPrice, ?Price $transportPrice = null, ?Price $paymentPrice = null, ?Price $roundingPrice = null, ?Price $transportFee = null): Price
    {
        $price = parent::calculateTotalPrice($productsPrice, $transportPrice, $paymentPrice, $roundingPrice);

        if ($transportFee !== null) {
            $price = $price->add($transportFee);
        }

        return $price;
    }
}
