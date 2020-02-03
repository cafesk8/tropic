<?php

declare(strict_types=1);

namespace App\Model\Order\Preview;

use Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreview as BaseOrderPreview;
use Shopsys\FrameworkBundle\Model\Payment\Payment;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Transport\Transport;
use App\Model\Order\PromoCode\PromoCode;
use App\Model\Order\PromoCode\PromoCodeData;

/**
 * @property \App\Model\Transport\Transport|null $transport
 * @property \App\Model\Payment\Payment|null $payment
 * @method \App\Model\Transport\Transport|null getTransport()
 * @method \App\Model\Payment\Payment|null getPayment()
 */
class OrderPreview extends BaseOrderPreview
{
    /**
     * @var \App\Model\Order\PromoCode\PromoCode[]
     */
    private $promoCodesIndexedById;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Price
     */
    private $totalDiscount;

    /**
     * @var \App\Model\Cart\Item\CartItem[]
     */
    private $gifts;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Price
     */
    private $totalPriceWithoutGiftCertificate;

    /**
     * @var \App\Model\Cart\Item\CartItem[]
     */
    private $promoProductCartItems;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Price[][]|mixed[][]
     */
    private $quantifiedItemsDiscountsIndexedByPromoCodeId;

    /**
     * @param array $quantifiedProductsByIndex
     * @param array $quantifiedItemsPricesByIndex
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $productsPrice
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $totalPrice
     * @param \App\Model\Transport\Transport|null $transport
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $transportPrice
     * @param \App\Model\Payment\Payment|null $payment
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $paymentPrice
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $roundingPrice
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $totalPriceWithoutGiftCertificate
     * @param \App\Model\Cart\Item\CartItem[] $gifts
     * @param \App\Model\Cart\Item\CartItem[] $promoProductCartItems
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price[][]|mixed[][] $quantifiedItemsDiscountsIndexedByPromoCodeId
     */
    public function __construct(
        array $quantifiedProductsByIndex,
        array $quantifiedItemsPricesByIndex,
        Price $productsPrice,
        Price $totalPrice,
        ?Transport $transport = null,
        ?Price $transportPrice = null,
        ?Payment $payment = null,
        ?Price $paymentPrice = null,
        ?Price $roundingPrice = null,
        ?Price $totalPriceWithoutGiftCertificate = null,
        array $gifts = [],
        array $promoProductCartItems = [],
        array $quantifiedItemsDiscountsIndexedByPromoCodeId = []
    ) {
        parent::__construct(
            $quantifiedProductsByIndex,
            $quantifiedItemsPricesByIndex,
            [],
            $productsPrice,
            $totalPrice,
            $transport,
            $transportPrice,
            $payment,
            $paymentPrice,
            $roundingPrice
        );

        $this->totalPriceWithoutGiftCertificate = $totalPriceWithoutGiftCertificate;
        $this->gifts = $gifts;
        $this->promoProductCartItems = $promoProductCartItems;
        $this->quantifiedItemsDiscountsIndexedByPromoCodeId = $quantifiedItemsDiscountsIndexedByPromoCodeId;
        $this->promoCodesIndexedById = [];
    }

    /**
     * @return \App\Model\Cart\Item\CartItem[]
     */
    public function getGifts(): ?array
    {
        return $this->gifts;
    }

    /**
     * @return \App\Model\Cart\Item\CartItem[]
     */
    public function getPromoProductCartItems(): array
    {
        return $this->promoProductCartItems;
    }

    /**
     * @param \App\Model\Order\PromoCode\PromoCode[] $promoCodes
     */
    public function setPromoCodes(array $promoCodes): void
    {
        foreach ($promoCodes as $promoCode) {
            $this->promoCodesIndexedById[$promoCode->getId()] = $promoCode;
        }
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $totalDiscount
     */
    public function setTotalDiscount(Price $totalDiscount): void
    {
        $this->totalDiscount = $totalDiscount;
    }

    /**
     * @return \App\Model\Order\PromoCode\PromoCode[]
     */
    public function getPromoCodesIndexedById(): array
    {
        return $this->promoCodesIndexedById;
    }

    /**
     * @param int $promoCodeId
     * @return \App\Model\Order\PromoCode\PromoCode
     */
    public function getPromoCodeById(int $promoCodeId): PromoCode
    {
        return $this->promoCodesIndexedById[$promoCodeId];
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price
     */
    public function getTotalDiscount(): Price
    {
        if ($this->totalDiscount === null) {
            return Price::zero();
        }

        return $this->totalDiscount;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price
     */
    public function getTotalPriceWithoutGiftCertificate(): Price
    {
        if ($this->totalPriceWithoutGiftCertificate === null) {
            return Price::zero();
        }

        return $this->totalPriceWithoutGiftCertificate;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice[] $quantifiedItemsPricesByIndex
     */
    public function setQuantifiedItemsPricesByIndex(array $quantifiedItemsPricesByIndex): void
    {
        $this->quantifiedItemsPricesByIndex = $quantifiedItemsPricesByIndex;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price[][]|mixed[][]
     */
    public function getQuantifiedItemsDiscountsIndexedByPromoCodeId(): array
    {
        return $this->quantifiedItemsDiscountsIndexedByPromoCodeId;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price[]
     */
    public function getTotalItemDiscountsIndexedByPromoCodeId(): array
    {
        $totalItemsDiscounts = [];
        foreach ($this->quantifiedItemsDiscountsIndexedByPromoCodeId as $promoCodeId => $quantifiedItemsDiscounts) {
            $promoCode = $this->getPromoCodeById($promoCodeId);
            if ($promoCode->getType() === PromoCodeData::TYPE_CERTIFICATE) {
                $totalItemsDiscounts[$promoCodeId] = new Price($promoCode->getCertificateValue(), $promoCode->getCertificateValue());
                continue;
            }

            $totalItemDiscount = Price::zero();
            foreach ($quantifiedItemsDiscounts as $quantifiedItemDiscount) {
                $addingAmount = $quantifiedItemDiscount === null ? Price::zero() : $quantifiedItemDiscount;
                $totalItemDiscount = $totalItemDiscount->add($addingAmount);
            }
            $totalItemsDiscounts[$promoCodeId] = $totalItemDiscount;
        }

        return $totalItemsDiscounts;
    }
}
