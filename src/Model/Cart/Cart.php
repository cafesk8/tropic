<?php

declare(strict_types=1);

namespace App\Model\Cart;

use App\Model\Cart\Item\CartItem;
use App\Model\Product\Product;
use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Cart\Cart as BaseCart;
use Shopsys\FrameworkBundle\Model\Cart\Item\CartItemFactoryInterface;
use Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedProduct;

/**
 * @ORM\Table(name="carts")
 * @ORM\Entity
 * @property \App\Model\Customer\User\CustomerUser|null $customerUser
 * @property \App\Model\Cart\Item\CartItem[]|\Doctrine\Common\Collections\Collection $items
 * @method __construct(string $cartIdentifier, \App\Model\Customer\User\CustomerUser|null $customerUser)
 * @method addItem(\App\Model\Cart\Item\CartItem $item)
 * @method \App\Model\Cart\Item\CartItem getItemById(int $itemId)
 * @method \App\Model\Cart\Item\CartItem|null findSimilarItemByItem(\App\Model\Cart\Item\CartItem $item)
 */
class Cart extends BaseCart
{
    /**
     * @var \App\Model\Product\Product|null
     *
     * @ORM\ManyToOne(targetEntity="App\Model\Product\Product")
     * @ORM\JoinColumn(onDelete="SET NULL", nullable=true)
     */
    private $orderGiftProduct;

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
     * @param \App\Model\Cart\Item\CartItemFactory $cartItemFactory
     * @param \App\Model\Product\Gift\ProductGiftInCart[][] $productGiftsInCart
     * @param mixed[][] $selectedGifts
     * @return \App\Model\Cart\Item\CartItem[]
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
     * @param int $productId
     * @return \App\Model\Cart\Item\CartItem
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
     * @return \App\Model\Cart\Item\CartItem[]
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
     * @param \App\Model\Cart\Item\CartItem $cartItem
     * @return \App\Model\Cart\Item\CartItem|null
     */
    public function removeGifByCartItem(CartItem $cartItem): ?CartItem
    {
        /** @var \App\Model\Cart\Item\CartItem $productInCart */
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
     * @return \App\Model\Cart\Item\CartItem[]
     */
    public function getGifts(): array
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
     * @return \App\Model\Cart\Item\CartItem[]
     */
    public function getItems()
    {
        $cartItems = [];
        /** @var \App\Model\Cart\Item\CartItem $cartItem */
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

    /**
     * @return \App\Model\Product\Product|null
     */
    public function getOrderGiftProduct(): ?Product
    {
        return $this->orderGiftProduct;
    }

    /**
     * @param \App\Model\Product\Product|null $orderGiftProduct
     */
    public function setOrderGiftProduct(?Product $orderGiftProduct): void
    {
        $this->orderGiftProduct = $orderGiftProduct;
    }
}
