<?php

declare(strict_types=1);

namespace App\Model\Cart;

use App\Model\Cart\Exception\MaxPromoProductCartItemsReachedException;
use App\Model\Cart\Item\CartItem;
use App\Model\Product\Product;
use App\Model\Product\ProductFacade;
use App\Model\Product\PromoProduct\PromoProduct;
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
    public const MAX_COUNT_OF_PROMO_PRODUCTS_IN_CART = 1;

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
     * @param \App\Model\Cart\Item\CartItemFactory $cartItemFactory
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Model\Product\PromoProduct\PromoProduct[][] $promoProductsForCart
     * @param mixed[][] $selectedPromoProductsItems
     * @return \App\Model\Cart\Item\CartItem[]
     */
    public function updatePromoProductsItems(
        CartItemFactoryInterface $cartItemFactory,
        ProductFacade $productFacade,
        array $promoProductsForCart,
        array $selectedPromoProductsItems
    ): array {
        $cartPromoProductsItems = [];

        $countOfSelected = 0;

        foreach ($selectedPromoProductsItems as $promoProductId => $isSelectedByProductId) {
            foreach ($isSelectedByProductId as $productId => $isSelected) {
                if ($isSelected === true) {
                    if ($countOfSelected >= self::MAX_COUNT_OF_PROMO_PRODUCTS_IN_CART) {
                        throw new MaxPromoProductCartItemsReachedException();
                    }

                    $countOfSelected++;

                    if (!isset($promoProductsForCart[$promoProductId])) {
                        continue;
                    }

                    /** @var \App\Model\Product\PromoProduct\PromoProduct $promoProductForCart */
                    $promoProductForCart = $promoProductsForCart[$promoProductId][$productId];

                    $promoProductCartItem = $cartItemFactory->create(
                        $this,
                        $productFacade->getById($productId),
                        1,
                        $promoProductForCart->getPrice(),
                        null,
                        null,
                        $promoProductForCart
                    );
                    $this->addItem($promoProductCartItem);

                    $cartPromoProductsItems[] = $promoProductCartItem;
                }
            }
        }

        return $cartPromoProductsItems;
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
     * @return \App\Model\Cart\Item\CartItem[]
     */
    public function removeAllPromoProductsAndGetThem(): array
    {
        $removedPromoProductsItems = [];
        foreach ($this->getPromoProductItems() as $promoProductItem) {
            $this->removeItemById($promoProductItem->getId());
            $removedPromoProductsItems[] = $promoProductItem;
        }

        return $removedPromoProductsItems;
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
    public function getPromoProductItems(): array
    {
        $promoProductCartItems = [];
        /** @var \App\Model\Cart\Item\CartItem $cartItem */
        foreach ($this->items->toArray() as $cartItem) {
            if ($cartItem->getPromoProduct() !== null) {
                $promoProductCartItems[] = $cartItem;
            }
        }

        return $promoProductCartItems;
    }

    /**
     * @return \App\Model\Cart\Item\CartItem[]
     */
    public function getItems()
    {
        $cartItems = [];
        /** @var \App\Model\Cart\Item\CartItem $cartItem */
        foreach ($this->items->toArray() as $cartItem) {
            if ($cartItem->getGiftByProduct() === null
            && $cartItem->getPromoProduct() === null
            ) {
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
     * @param \App\Model\Product\PromoProduct\PromoProduct $promoProduct
     * @param int $productId
     * @return bool
     */
    public function isPromoProductSelected(PromoProduct $promoProduct, int $productId): bool
    {
        foreach ($this->getPromoProductItems() as $promoProductCartItem) {
            if ($promoProductCartItem->getPromoProduct() === $promoProduct
                && $promoProductCartItem->getProduct()->getId() === $productId) {
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
