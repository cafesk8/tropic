<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Gift;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Cart\Item\CartItem;
use Shopsys\ShopBundle\Model\Product\Product;
use Shopsys\ShopBundle\Model\Product\ProductFacade;

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
     * @var \Shopsys\ShopBundle\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Gift\ProductGiftPriceCalculation $productGiftCalculation
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade
     */
    public function __construct(ProductGiftPriceCalculation $productGiftCalculation, Domain $domain, ProductFacade $productFacade)
    {
        $this->productGiftCalculation = $productGiftCalculation;
        $this->domain = $domain;
        $this->productFacade = $productFacade;
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
     * @param \Shopsys\ShopBundle\Model\Product\Product $productGift
     * @param \Shopsys\FrameworkBundle\Model\Cart\Item\CartItem $cartItem
     * @return \Shopsys\ShopBundle\Model\Product\Gift\ProductGiftInCart[]
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
