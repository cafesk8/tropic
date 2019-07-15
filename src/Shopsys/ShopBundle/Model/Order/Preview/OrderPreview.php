<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Preview;

use Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreview as BaseOrderPreview;
use Shopsys\FrameworkBundle\Model\Payment\Payment;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Transport\Transport;

class OrderPreview extends BaseOrderPreview
{
    /**
     * @var \Shopsys\ShopBundle\Model\Cart\Item\CartItem[]
     */
    private $gifts;

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
     * @param \Shopsys\ShopBundle\Model\Cart\Item\CartItem[] $gifts
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
        array $gifts = []
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

        $this->gifts = $gifts;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Cart\Item\CartItem[]
     */
    public function getGifts(): ?array
    {
        return $this->gifts;
    }
}
