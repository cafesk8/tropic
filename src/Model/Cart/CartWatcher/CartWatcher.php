<?php

declare(strict_types=1);

namespace App\Model\Cart\CartWatcher;

use Shopsys\FrameworkBundle\Model\Cart\Cart;
use Shopsys\FrameworkBundle\Model\Cart\Watcher\CartWatcher as BaseCartWatcher;

/**
 * @property \App\Model\Product\Pricing\ProductPriceCalculationForCustomerUser $productPriceCalculationForCustomerUser
 * @method __construct(\App\Model\Product\Pricing\ProductPriceCalculationForCustomerUser $productPriceCalculationForCustomerUser, \Shopsys\FrameworkBundle\Model\Product\ProductVisibilityRepository $productVisibilityRepository, \Shopsys\FrameworkBundle\Component\Domain\Domain $domain)
 * @method \App\Model\Cart\Item\CartItem[] getNotListableItems(\App\Model\Cart\Cart $cart, \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser $currentCustomerUser)
 */
class CartWatcher extends BaseCartWatcher
{
    /**
     * product price calculation distinguishes sale and non-sale items in the cart
     * @param \App\Model\Cart\Cart $cart
     * @return \App\Model\Cart\Item\CartItem[]
     */
    public function getModifiedPriceItemsAndUpdatePrices(Cart $cart)
    {
        $modifiedItems = [];
        foreach ($cart->getItems() as $cartItem) {
            /** @var \App\Model\Cart\Item\CartItem $cartItem */
            $productPrice = $this->productPriceCalculationForCustomerUser->calculatePriceForCurrentUser(
                $cartItem->getProduct(),
                $cartItem->isSaleItem()
            );
            if (!$productPrice->getPriceWithVat()->equals($cartItem->getWatchedPrice())) {
                $modifiedItems[] = $cartItem;
            }
            $cartItem->setWatchedPrice($productPrice->getPriceWithVat());
        }
        return $modifiedItems;
    }
}
