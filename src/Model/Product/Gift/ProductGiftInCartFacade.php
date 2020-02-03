<?php

declare(strict_types=1);

namespace App\Model\Product\Gift;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Cart\Item\CartItem;
use App\Model\Product\Product;
use App\Model\Product\ProductFacade;

class ProductGiftInCartFacade
{
    /**
     * @var \App\Model\Product\Gift\ProductGiftPriceCalculation
     */
    private $productGiftCalculation;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \App\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @param \App\Model\Product\Gift\ProductGiftPriceCalculation $productGiftCalculation
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Product\ProductFacade $productFacade
     */
    public function __construct(ProductGiftPriceCalculation $productGiftCalculation, Domain $domain, ProductFacade $productFacade)
    {
        $this->productGiftCalculation = $productGiftCalculation;
        $this->domain = $domain;
        $this->productFacade = $productFacade;
    }

    /**
     * @param \App\Model\Cart\Item\CartItem[] $cartItems
     * @return \App\Model\Product\Gift\ProductGiftInCart[][]
     */
    public function getProductGiftInCartByProductId(array $cartItems): array
    {
        $giftsVariantsByProductId = [];

        /** @var \App\Model\Cart\Item\CartItem $cartItem */
        foreach ($cartItems as $cartItem) {
            if ($cartItem->getProduct()->isVariant() === true) {
                $productGifts = $cartItem->getProduct()->getMainVariant()->getActiveProductGiftsByDomainId($this->domain->getId());
            } else {
                $productGifts = $cartItem->getProduct()->getActiveProductGiftsByDomainId($this->domain->getId());
            }

            foreach ($productGifts as $productGift) {
                /** @var \App\Model\Product\Product $gift */
                $gift = $productGift->getGift();

                if ($this->productFacade->isProductMarketable($gift)) {
                    foreach ($this->getGiftVariants($gift, $cartItem) as $giftVariantIndex => $giftVariant) {
                        $giftsVariantsByProductId[$cartItem->getProduct()->getId()][$giftVariantIndex] = $giftVariant;
                    }
                }
            }
        }

        return $giftsVariantsByProductId;
    }

    /**
     * @param \App\Model\Product\Product $productGift
     * @param \App\Model\Cart\Item\CartItem $cartItem
     * @return \App\Model\Product\Gift\ProductGiftInCart[]
     */
    private function getGiftVariants(Product $productGift, CartItem $cartItem): array
    {
        $giftVariantsByProductId = [];
        if ($productGift->isMainVariant() && count($productGift->getVariants()) > 0) {
            foreach ($productGift->getVariants() as $giftVariant) {
                if ($this->productFacade->isProductMarketable($giftVariant) === false) {
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
}
