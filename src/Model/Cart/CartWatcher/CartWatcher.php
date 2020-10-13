<?php

declare(strict_types=1);

namespace App\Model\Cart\CartWatcher;

use App\Model\Product\ProductCachedAttributesFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Cart\Cart;
use Shopsys\FrameworkBundle\Model\Cart\Watcher\CartWatcher as BaseCartWatcher;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForCustomerUser;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibilityRepository;

/**
 * @property \App\Model\Product\Pricing\ProductPriceCalculationForCustomerUser $productPriceCalculationForCustomerUser
 * @method \App\Model\Cart\Item\CartItem[] getNotListableItems(\App\Model\Cart\Cart $cart, \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser $currentCustomerUser)
 * @property \App\Model\Product\ProductVisibilityRepository $productVisibilityRepository
 */
class CartWatcher extends BaseCartWatcher
{
    private ProductCachedAttributesFacade $productCachedAttributesFacade;

    /**
     * @param \App\Model\Product\Pricing\ProductPriceCalculationForCustomerUser $productPriceCalculationForCustomerUser
     * @param \App\Model\Product\ProductVisibilityRepository $productVisibilityRepository
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Product\ProductCachedAttributesFacade $productCachedAttributesFacade
     */
    public function __construct(
        ProductPriceCalculationForCustomerUser $productPriceCalculationForCustomerUser,
        ProductVisibilityRepository $productVisibilityRepository,
        Domain $domain,
        ProductCachedAttributesFacade $productCachedAttributesFacade
    ) {
        parent::__construct($productPriceCalculationForCustomerUser, $productVisibilityRepository, $domain);
        $this->productCachedAttributesFacade = $productCachedAttributesFacade;
    }

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
            $productPrice = $this->productCachedAttributesFacade->calculateProductSellingPriceAndSaveToCache(
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
