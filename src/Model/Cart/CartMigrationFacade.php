<?php

declare(strict_types=1);

namespace App\Model\Cart;

use Shopsys\FrameworkBundle\Model\Cart\Cart;
use Shopsys\FrameworkBundle\Model\Cart\CartMigrationFacade as BaseCartMigrationFacade;
use Shopsys\FrameworkBundle\Model\Cart\Item\CartItem;

/**
 * @property \App\Model\Cart\Item\CartItemFactory $cartItemFactory
 * @property \App\Model\Cart\CartFacade $cartFacade
 * @method __construct(\Doctrine\ORM\EntityManagerInterface $em, \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserIdentifierFactory $customerUserIdentifierFactory, \App\Model\Cart\Item\CartItemFactory $cartItemFactory, \App\Model\Cart\CartFacade $cartFacade)
 */
class CartMigrationFacade extends BaseCartMigrationFacade
{
    /**
     * @param \App\Model\Cart\Cart $cart
     */
    public function mergeCurrentCartWithCart(Cart $cart): void
    {
        $customerUserIdentifier = $this->customerUserIdentifierFactory->get();
        $currentCart = $this->cartFacade->getCartByCustomerUserIdentifierCreateIfNotExists($customerUserIdentifier);
        foreach ($cart->getItems() as $itemToMerge) {
            $similarItem = $currentCart->findSimilarItemByItem($itemToMerge);
            if ($similarItem instanceof CartItem) {
                $similarItem->changeQuantity($similarItem->getQuantity() + $itemToMerge->getQuantity());
            } else {
                $newCartItem = $this->cartItemFactory->create(
                    $currentCart,
                    $itemToMerge->getProduct(),
                    $itemToMerge->getQuantity(),
                    $itemToMerge->getWatchedPrice(),
                    null,
                    null,
                    $itemToMerge->isSaleItem()
                );
                $currentCart->addItem($newCartItem);
            }
        }
        $currentCart->setModifiedNow();

        foreach ($currentCart->getItems() as $item) {
            $this->em->persist($item);
        }

        $this->cartFacade->deleteCart($cart);

        $this->em->flush();
    }
}
