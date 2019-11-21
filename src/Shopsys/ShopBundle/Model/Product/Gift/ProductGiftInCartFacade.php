<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Gift;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Cart\Item\CartItem;
use Shopsys\ShopBundle\Model\Product\Product;

class ProductGiftInCartFacade
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\Gift\ProductGiftPriceCalculation
     */
    private $productGiftCalculation;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Gift\ProductGiftPriceCalculation $productGiftCalculation
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(ProductGiftPriceCalculation $productGiftCalculation, Domain $domain)
    {
        $this->productGiftCalculation = $productGiftCalculation;
        $this->domain = $domain;
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
                $productGifts = $cartItem->getProduct()->getMainVariant()->getActiveProductGiftsByDomainId($this->domain->getId());
            } else {
                $productGifts = $cartItem->getProduct()->getActiveProductGiftsByDomainId($this->domain->getId());
            }

            foreach ($productGifts as $productGift) {
                /** @var \Shopsys\ShopBundle\Model\Product\Product $gift */
                $gift = $productGift->getGift();

                if ($this->isGiftMarketable($gift)) {
                    foreach ($this->getGiftVariants($gift, $cartItem) as $giftVariantIndex => $giftVariant) {
                        $giftsVariantsByProductId[$cartItem->getProduct()->getId()][$giftVariantIndex] = $giftVariant;
                    }
                }
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
