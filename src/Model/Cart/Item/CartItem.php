<?php

declare(strict_types=1);

namespace App\Model\Cart\Item;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Cart\Cart;
use Shopsys\FrameworkBundle\Model\Cart\Item\CartItem as BaseCartItem;
use App\Model\Product\Product;
use App\Model\Product\PromoProduct\PromoProduct;

/**
 * @method \App\Model\Product\Product getProduct()
 *
 * @ORM\Table(name="cart_items")
 * @ORM\Entity
 * @property \App\Model\Product\Product|null $product
 * @method bool isSimilarItemAs(\App\Model\Cart\Item\CartItem $cartItem)
 */
class CartItem extends BaseCartItem
{
    /**
     * @var \App\Model\Product\Product|null
     *
     * @ORM\ManyToOne(targetEntity="Shopsys\FrameworkBundle\Model\Product\Product")
     * @ORM\JoinColumn(nullable=true, name="gift_by_product_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $giftByProduct;

    /**
     * @var \App\Model\Cart\Item\CartItem|null
     *
     * @ORM\ManyToOne(targetEntity="App\Model\Cart\Item\CartItem")
     * @ORM\JoinColumn(name="main_cart_item_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    private $mainCartItem;

    /**
     * @var \App\Model\Cart\Cart
     *
     * @ORM\ManyToOne(targetEntity="Shopsys\FrameworkBundle\Model\Cart\Cart", inversedBy="items")
     * @ORM\JoinColumn(name="cart_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $cart;

    /**
     * @var \App\Model\Product\PromoProduct\PromoProduct|null
     *
     * @ORM\ManyToOne(targetEntity="App\Model\Product\PromoProduct\PromoProduct")
     * @ORM\JoinColumn(nullable=true, name="promo_product_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $promoProduct;

    /**
     * @param \App\Model\Cart\Cart $cart
     * @param \App\Model\Product\Product $product
     * @param int $quantity
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $watchedPrice
     * @param \App\Model\Product\Product|null $giftByProduct
     * @param \App\Model\Cart\Item\CartItem|null $mainCartItem
     * @param \App\Model\Product\PromoProduct\PromoProduct|null $promoProduct
     */
    public function __construct(
        Cart $cart,
        Product $product,
        int $quantity,
        ?Money $watchedPrice,
        ?Product $giftByProduct = null,
        ?self $mainCartItem = null,
        ?PromoProduct $promoProduct = null
    ) {
        parent::__construct($cart, $product, $quantity, $watchedPrice);

        $this->giftByProduct = $giftByProduct;
        $this->mainCartItem = $mainCartItem;
        $this->promoProduct = $promoProduct;
    }

    /**
     * @return \App\Model\Product\Product:null
     */
    public function getGiftByProduct(): ?Product
    {
        return $this->giftByProduct;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Money\Money
     */
    public function getTotalPrice(): Money
    {
        return $this->watchedPrice->multiply($this->quantity);
    }

    /**
     * @return \App\Model\Cart\Item\CartItem|null
     */
    public function getMainCartItem(): ?self
    {
        return $this->mainCartItem;
    }

    /**
     * @return \App\Model\Product\PromoProduct\PromoProduct|null
     */
    public function getPromoProduct(): ?PromoProduct
    {
        return $this->promoProduct;
    }
}
