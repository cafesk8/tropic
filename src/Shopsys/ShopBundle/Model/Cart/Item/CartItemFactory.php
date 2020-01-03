<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Cart\Item;

use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Cart\Cart;
use Shopsys\FrameworkBundle\Model\Cart\Item\CartItem as BaseCartItem;
use Shopsys\FrameworkBundle\Model\Cart\Item\CartItemFactory as BaseCartItemFactory;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProduct;

class CartItemFactory extends BaseCartItemFactory
{
    /**
     * @param \Shopsys\FrameworkBundle\Model\Cart\Cart $cart
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @param int $quantity
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $watchedPrice
     * @param \Shopsys\FrameworkBundle\Model\Product\Product|null $gift
     * @param \Shopsys\ShopBundle\Model\Cart\Item\CartItem|null $mainCartItem
     * @param \Shopsys\ShopBundle\Model\Cart\Item\PromoProduct|null $promoProduct
     * @return \Shopsys\FrameworkBundle\Model\Cart\Item\CartItem
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
