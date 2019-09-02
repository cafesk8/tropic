<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Gift;

use Shopsys\FrameworkBundle\Model\Cart\Item\CartItem;
use Shopsys\ShopBundle\Model\Product\Product;

class ProductGiftFacade
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\Gift\ProductGiftPriceCalculation
     */
    private $productGiftCalculation;

    /**
     * ProductGiftFacade constructor.
     * @param \Shopsys\ShopBundle\Model\Product\Gift\ProductGiftPriceCalculation $productGiftCalculation
     */
    public function __construct(ProductGiftPriceCalculation $productGiftCalculation)
    {
        $this->productGiftCalculation = $productGiftCalculation;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Cart\Item\CartItem[] $cartItems
     * @return \Shopsys\ShopBundle\Model\Product\Gift\ProductGiftInCart[]
     */
    public function getProductGiftInCartByProductId(array $cartItems): array
    {
        $giftsVariantsByProductId = [];

        /** @var \Shopsys\FrameworkBundle\Model\Cart\Item\CartItem $cartItem */
        foreach ($cartItems as $cartItem) {
            if ($cartItem->getProduct()->isVariant() === true) {
                /** @var \Shopsys\ShopBundle\Model\Product\Product $gift */
                $gift = $cartItem->getProduct()->getMainVariant()->getGift();
            } else {
                /** @var \Shopsys\ShopBundle\Model\Product\Product $gift */
                $gift = $cartItem->getProduct()->getGift();
            }

            if ($this->isGiftMarketable($gift)) {
                $giftsVariantsByProductId[$cartItem->getProduct()->getId()] = $this->getGiftVariants($gift, $cartItem);
            }
        }

        return $giftsVariantsByProductId;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $productGift
     * @param \Shopsys\FrameworkBundle\Model\Cart\Item\CartItem $cartItem
     * @return \Shopsys\ShopBundle\Model\Product\Gift\ProductGiftInCart[]
     */
    private function getGiftVariants(Product $productGift, CartItem $cartItem): array
    {
        $giftVariantsByProductId = [];
        if ($productGift->isMainVariant() && count($productGift->getVariants()) > 0) {
            foreach ($productGift->getVariants() as $giftVariant) {
                if ($this->isGiftMarketable($giftVariant) === false) {
                    continue;
                }

                $giftVariantsByProductId[$giftVariant->getId()] = new ProductGiftInCart(
                    $cartItem->getProduct(),
                    $giftVariant,
                    $this->productGiftCalculation->getGiftPrice(),
                    $cartItem->getQuantity()
                );
            }
        } else {
            $giftVariantsByProductId[$productGift->getId()] = new ProductGiftInCart(
                $cartItem->getProduct(),
                $productGift,
                $this->productGiftCalculation->getGiftPrice(),
                $cartItem->getQuantity()
            );
        }

        return $giftVariantsByProductId;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product|null $gift
     * @return bool
     */
    private function isGiftMarketable(?Product $gift): bool
    {
        return $gift !== null && $gift->isHidden() === false && $gift->getCalculatedHidden() === false &&
            $gift->isSellingDenied() === false && $gift->getCalculatedSellingDenied() === false;
    }
}
