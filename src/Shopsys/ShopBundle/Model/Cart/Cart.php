<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Cart;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Cart\Cart as BaseCart;
use Shopsys\FrameworkBundle\Model\Cart\Item\CartItemFactoryInterface;
use Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedProduct;
use Shopsys\ShopBundle\Model\Cart\Item\CartItem;

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
            $cartProductsValue = $cartProductsValue->add(
                $cartItem->getWatchedPrice()->multiply($cartItem->getQuantity())
            );
        }

        return $cartProductsValue;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Cart\Item\CartItemFactoryInterface $cartItemFactory
     * @param \Shopsys\ShopBundle\Model\Product\Gift\ProductGiftInCart[] $productGiftsInCart
     * @param mixed[] $selectedGifts
     * @return \Shopsys\ShopBundle\Model\Cart\Item\CartItem[]
     */
    public function updateGifts(CartItemFactoryInterface $cartItemFactory, array $productGiftsInCart, array $selectedGifts): array
    {
        $cartGifts = [];

        foreach ($selectedGifts as $giftMainVariantId => $giftVariants) {
            foreach ($giftVariants as $selectedVariantGiftId => $isSelected) {
                if ($isSelected === true) {
                    $productGiftInCart = $productGiftsInCart[$giftMainVariantId][$selectedVariantGiftId];

                    $cartGift = $cartItemFactory->create(
                        $this,
                        $productGiftInCart->getGift(),
                        $productGiftInCart->getQuantity(),
                        $productGiftInCart->getPrice(),
                        $productGiftInCart->getProduct(),
                        $this->getItemByProductId($productGiftInCart->getProduct()->getId())
                    );
                    $this->addItem($cartGift);

                    $cartGifts[] = $cartGift;
                }
            }
        }

        return $cartGifts;
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

    /**
     * @return array
     */
    public function removeAllGift(): array
    {
        $removedGifts = [];
        foreach ($this->getGifts() as $gift) {
            $this->removeItemById($gift->getId());
            $removedGifts[] = $gift;
        }

        return $removedGifts;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Cart\Item\CartItem $cartItem
     * @return \Shopsys\ShopBundle\Model\Cart\Item\CartItem|null
     */
    public function removeGifByCartItem(CartItem $cartItem): ?CartItem
    {
        /** @var \Shopsys\ShopBundle\Model\Cart\Item\CartItem $productInCart */
        foreach ($this->items as $productInCart) {
            if ($productInCart->getGiftByProduct() === null) {
                continue;
            }

            if ($productInCart->getProduct() === $cartItem->getProduct()) {
                $this->items->removeElement($productInCart);
                return $productInCart;
            }
        }

        return null;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection|\Shopsys\ShopBundle\Model\Cart\Item\CartItem[]
     */
    public function getGifts()
    {
        $cartGifts = [];
        foreach ($this->items->toArray() as $cartItem) {
            if ($cartItem->getGiftByProduct() !== null) {
                $cartGifts[] = $cartItem;
            }
        }

        return $cartGifts;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Cart\Item\CartItem[]
     */
    public function getItems()
    {
        $cartItems = [];
        foreach ($this->items->toArray() as $cartItem) {
            if ($cartItem->getGiftByProduct() === null) {
                $cartItems[] = $cartItem;
            }
        }

        return $cartItems;
    }

    /**
     * @param int $giftId
     * @param int $cartItemId
     * @return bool
     */
    public function isProductGiftSelected(int $giftId, int $cartItemId): bool
    {
        foreach ($this->getGifts() as $cartGift) {
            if ($cartGift->getProduct()->getId() === $giftId && $cartGift->getGiftByProduct()->getId() === $cartItemId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedProduct[]
     */
    public function getQuantifiedProducts()
    {
        $quantifiedProducts = [];
        foreach ($this->getItems() as $item) {
            $quantifiedProducts[] = new QuantifiedProduct($item->getProduct(), $item->getQuantity());
        }

        return $quantifiedProducts;
    }
}
