<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Cart;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Cart\Cart as BaseCart;
use Shopsys\FrameworkBundle\Model\Cart\Item\CartItem;

/**
 * @ORM\Table(name="carts")
 * @ORM\Entity
 */
class Cart extends BaseCart
{
    /**
     * @return \Shopsys\FrameworkBundle\Component\Money\Money
     */
    public function getTotalWatchedPriceOfProducts(): Money
    {
        $cartProductsValue = Money::zero();
        foreach ($this->getItems() as $cartItem) {
            $cartProductsValue = $cartProductsValue->add($cartItem->getWatchedPrice()->multiply($cartItem->getQuantity()));
        }

        return $cartProductsValue;
    }

    /**
     * @param $productId
     * @return \Shopsys\FrameworkBundle\Model\Cart\Item\CartItem
     */
    public function getItemByProductId(int $productId): CartItem
    {
        foreach ($this->items as $item) {
            if ($item->getProduct()->getId() === $productId) {
                return $item;
            }
        }
        $message = 'CartItem with product id = ' . $productId . ' not found in cart.';
        throw new \Shopsys\FrameworkBundle\Model\Cart\Exception\InvalidCartItemException($message);
    }
}
