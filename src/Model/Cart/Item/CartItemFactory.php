<?php

declare(strict_types=1);

namespace App\Model\Cart\Item;

use App\Model\Product\PromoProduct\PromoProduct;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Cart\Cart;
use Shopsys\FrameworkBundle\Model\Cart\Item\CartItem as BaseCartItem;
use Shopsys\FrameworkBundle\Model\Cart\Item\CartItemFactory as BaseCartItemFactory;
use Shopsys\FrameworkBundle\Model\Product\Product;

class CartItemFactory extends BaseCartItemFactory
{
    /**
     * @param \App\Model\Cart\Cart $cart
     * @param \App\Model\Product\Product $product
     * @param int $quantity
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $watchedPrice
     * @param \App\Model\Product\Product|null $gift
     * @param \App\Model\Cart\Item\CartItem|null $mainCartItem
     * @param \App\Model\Product\PromoProduct\PromoProduct|null $promoProduct
     * @return \App\Model\Cart\Item\CartItem
     */
    public function create(
        Cart $cart,
        Product $product,
        int $quantity,
        ?Money $watchedPrice,
        ?Product $gift = null,
        ?CartItem $mainCartItem = null,
        ?PromoProduct $promoProduct = null
    ): BaseCartItem {
        return new CartItem($cart, $product, $quantity, $watchedPrice, $gift, $mainCartItem, $promoProduct);
    }
}
