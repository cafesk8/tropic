<?php

declare(strict_types=1);

namespace App\Model\Order\Preview;

use App\Model\Cart\Item\CartItem;
use App\Model\Order\Discount\OrderDiscountLevel;
use App\Model\Order\PromoCode\PromoCode;
use Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreview as BaseOrderPreview;
use Shopsys\FrameworkBundle\Model\Payment\Payment;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Transport\Transport;

/**
 * @property \App\Model\Transport\Transport|null $transport
 * @property \App\Model\Payment\Payment|null $payment
 * @property \App\Model\Order\Item\QuantifiedProduct[] $quantifiedProductsByIndex
 * @method \App\Model\Transport\Transport|null getTransport()
 * @method \App\Model\Payment\Payment|null getPayment()
 * @method \App\Model\Order\Item\QuantifiedProduct[] getQuantifiedProducts()
 */
class OrderPreview extends BaseOrderPreview
{
    /**
     * @var \App\Model\Product\Product|null
     */
    protected $orderGiftProduct;

    /**
     * @var \App\Model\Order\Discount\OrderDiscountLevel|null
     */
    protected $activeOrderDiscountLevel;

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
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Price[][]|mixed[][]
     */
    private $quantifiedItemsDiscountsIndexedByPromoCodeId;

    /**
     * @param array $quantifiedProductsByIndex
     * @param array $quantifiedItemsPricesByIndex
     * @param array $quantifiedItemsDiscountsByIndex
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $productsPrice
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $totalPrice
     * @param \App\Model\Transport\Transport|null $transport
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $transportPrice
     * @param \App\Model\Payment\Payment|null $payment
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $paymentPrice
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $roundingPrice
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $totalPriceWithoutGiftCertificate
     * @param \App\Model\Cart\Item\CartItem[] $gifts
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price[][]|mixed[][] $quantifiedItemsDiscountsIndexedByPromoCodeId
     * @param \App\Model\Product\Product|null $orderGiftProduct
     * @param \App\Model\Order\Discount\OrderDiscountLevel|null $activeOrderDiscountLevel
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
        ?Price $totalPriceWithoutGiftCertificate = null,
        array $gifts = [],
        array $quantifiedItemsDiscountsIndexedByPromoCodeId = [],
        ?Product $orderGiftProduct = null,
        ?OrderDiscountLevel $activeOrderDiscountLevel = null
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
            $roundingPrice
        );

        $this->totalPriceWithoutGiftCertificate = $totalPriceWithoutGiftCertificate;
        $this->gifts = $gifts;
        $this->quantifiedItemsDiscountsIndexedByPromoCodeId = $quantifiedItemsDiscountsIndexedByPromoCodeId;
        $this->promoCodesIndexedById = [];
        $this->orderGiftProduct = $orderGiftProduct;
        $this->activeOrderDiscountLevel = $activeOrderDiscountLevel;
    }

    /**
     * @return \App\Model\Product\Product|null
     */
    public function getOrderGiftProduct(): ?Product
    {
        return $this->orderGiftProduct;
    }

    /**
     * @return \App\Model\Cart\Item\CartItem[]
     */
    public function getGifts(): ?array
    {
        return $this->gifts;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return \App\Model\Cart\Item\CartItem|null
     */
    public function getGiftForProduct(Product $product): ?CartItem
    {
        foreach ($this->gifts as $gift) {
            if ($gift->getGiftByProduct() !== null && $gift->getGiftByProduct()->getId() === $product->getId()) {
                return $gift;
            }
        }

        return null;
    }

    /**
     * @param \App\Model\Order\PromoCode\PromoCode[] $promoCodes
     */
    public function setPromoCodes(array $promoCodes): void
    {
        if (empty($promoCodes)) {
            $this->promoCodesIndexedById = [];
        }
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
            if ($promoCode->isTypeGiftCertificate()) {
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

    /**
     * @return \App\Model\Order\Discount\OrderDiscountLevel|null
     */
    public function getActiveOrderDiscountLevel(): ?OrderDiscountLevel
    {
        return $this->activeOrderDiscountLevel;
    }
}
