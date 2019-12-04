<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Preview;

use Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreview as BaseOrderPreview;
use Shopsys\FrameworkBundle\Model\Payment\Payment;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Transport\Transport;
use Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode;

class OrderPreview extends BaseOrderPreview
{
    /**
     * @var \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode|null
     */
    private $promoCode;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Price
     */
    private $totalDiscount;

    /**
     * @var \Shopsys\ShopBundle\Model\Cart\Item\CartItem[]
     */
    private $gifts;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Price
     */
    private $totalPriceWithoutGiftCertificate;

    /**
     * @var \Shopsys\ShopBundle\Model\Cart\Item\CartItem[]
     */
    private $promoProductCartItems;

    /**
     * @param array $quantifiedProductsByIndex
     * @param array $quantifiedItemsPricesByIndex
     * @param array $quantifiedItemsDiscountsByIndex
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $productsPrice
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $totalPrice
     * @param \Shopsys\FrameworkBundle\Model\Transport\Transport|null $transport
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $transportPrice
     * @param \Shopsys\FrameworkBundle\Model\Payment\Payment|null $payment
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $paymentPrice
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $roundingPrice
     * @param string|null $promoCodeDiscountPercent
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $totalPriceWithoutGiftCertificate
     * @param \Shopsys\ShopBundle\Model\Cart\Item\CartItem[] $gifts
     * @param \Shopsys\ShopBundle\Model\Cart\Item\CartItem[] $promoProductCartItems
     */
    public function __construct(
        array $quantifiedProductsByIndex,
        array $quantifiedItemsPricesByIndex,
        array $quantifiedItemsDiscountsByIndex,
        Price $productsPrice,
        Price $totalPrice,
        ?Transport $transport = null,
        ?Price $transportPrice = null,
        ?Payment $payment = null,
        ?Price $paymentPrice = null,
        ?Price $roundingPrice = null,
        ?string $promoCodeDiscountPercent = null,
        ?Price $totalPriceWithoutGiftCertificate = null,
        array $gifts = [],
        array $promoProductCartItems = []
    ) {
        parent::__construct(
            $quantifiedProductsByIndex,
            $quantifiedItemsPricesByIndex,
            $quantifiedItemsDiscountsByIndex,
            $productsPrice,
            $totalPrice,
            $transport,
            $transportPrice,
            $payment,
            $paymentPrice,
            $roundingPrice,
            $promoCodeDiscountPercent
        );

        $this->totalPriceWithoutGiftCertificate = $totalPriceWithoutGiftCertificate;
        $this->gifts = $gifts;
        $this->promoProductCartItems = $promoProductCartItems;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Cart\Item\CartItem[]
     */
    public function getGifts(): ?array
    {
        return $this->gifts;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Cart\Item\CartItem[]
     */
    public function getPromoProductCartItems(): array
    {
        return $this->promoProductCartItems;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode|null $promoCode
     */
    public function setPromoCode(?PromoCode $promoCode): void
    {
        $this->promoCode = $promoCode;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $totalDiscount
     */
    public function setTotalDiscount(Price $totalDiscount): void
    {
        $this->totalDiscount = $totalDiscount;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode|null
     */
    public function getPromoCode(): ?PromoCode
    {
        return $this->promoCode;
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
}
