<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Preview;

use Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreview as BaseOrderPreview;
use Shopsys\FrameworkBundle\Model\Payment\Payment;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Transport\Transport;
use Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode;
use Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeData;

class OrderPreview extends BaseOrderPreview
{
    /**
     * @var \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode[]
     */
    private $promoCodesIndexedById;

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
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Price[][]
     */
    private $quantifiedItemsDiscountsIndexedByPromoCodeId;

    /**
     * @param array $quantifiedProductsByIndex
     * @param array $quantifiedItemsPricesByIndex
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $productsPrice
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $totalPrice
     * @param \Shopsys\FrameworkBundle\Model\Transport\Transport|null $transport
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $transportPrice
     * @param \Shopsys\FrameworkBundle\Model\Payment\Payment|null $payment
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $paymentPrice
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $roundingPrice
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $totalPriceWithoutGiftCertificate
     * @param \Shopsys\ShopBundle\Model\Cart\Item\CartItem[] $gifts
     * @param \Shopsys\ShopBundle\Model\Cart\Item\CartItem[] $promoProductCartItems
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price[][] $quantifiedItemsDiscountsIndexedByPromoCodeId
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
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode[] $promoCodes
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
     * @return \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode[]
     */
    public function getPromoCodesIndexedById(): array
    {
        return $this->promoCodesIndexedById;
    }

    /**
     * @param int $promoCodeId
     * @return \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode
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
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price[][]
     */
    public function getQuantifiedItemsDiscountsIndexedByPromoCodeId(): array
    {
        return $this->quantifiedItemsDiscountsIndexedByPromoCodeId;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price[][]
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
