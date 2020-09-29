<?php

declare(strict_types=1);

namespace App\Model\Product\Gift;

use App\Model\Product\Product;
use App\Model\Product\ProductFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Cart\Item\CartItem;

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
        $alreadyUsedGiftQuantitiesIndexedByProductId = [];

        foreach ($cartItems as $cartItem) {
            if ($cartItem->getProduct()->isVariant() === true) {
                $productGifts = $cartItem->getProduct()->getMainVariant()->getActiveInStockProductGiftsByDomainId($this->domain->getId());
            } else {
                $productGifts = $cartItem->getProduct()->getActiveInStockProductGiftsByDomainId($this->domain->getId());
            }

            $remainingQuantity = $cartItem->getQuantity();

            foreach ($productGifts as $productGift) {
                $gift = $productGift->getGift();

                if ($this->productFacade->isProductMarketable($gift, $this->domain->getId())) {
                    foreach ($this->getGiftVariants($gift, $cartItem, $remainingQuantity) as $giftVariantIndex => $giftVariant) {
                        if (!isset($alreadyUsedGiftQuantitiesIndexedByProductId[$giftVariantIndex])) {
                            $alreadyUsedGiftQuantitiesIndexedByProductId[$giftVariantIndex] = 0;
                        }

                        if ($giftVariant->getGift()->getRealStockQuantity() <= $alreadyUsedGiftQuantitiesIndexedByProductId[$giftVariant->getGift()->getId()]) {
                            continue;
                        }

                        $giftsVariantsByProductId[$cartItem->getId()][$giftVariantIndex] = $giftVariant;
                        $alreadyUsedGiftQuantitiesIndexedByProductId[$giftVariantIndex] += $giftVariant->getQuantity();
                        $remainingQuantity -= $giftVariant->getQuantity();

                        if ($remainingQuantity < 1) {
                            break 2;
                        }
                    }
                }
            }
        }

        return $giftsVariantsByProductId;
    }

    /**
     * @param \App\Model\Product\Product $productGift
     * @param \App\Model\Cart\Item\CartItem $cartItem
     * @param int $quantity
     * @return \App\Model\Product\Gift\ProductGiftInCart[]
     */
    private function getGiftVariants(Product $productGift, CartItem $cartItem, int $quantity): array
    {
        $giftVariantsByProductId = [];
        if ($productGift->isMainVariant() && count($productGift->getVariants()) > 0) {
            foreach ($productGift->getVariants() as $giftVariant) {
                if ($this->productFacade->isProductMarketable($giftVariant, $this->domain->getId()) === false) {
                    continue;
                }

                $giftVariantsByProductId[$giftVariant->getId()] = new ProductGiftInCart(
                    $cartItem->getProduct(),
                    $giftVariant,
                    $this->productGiftCalculation->getGiftPrice(),
                    $giftVariant->getAvailableQuantity($quantity)
                );
                $quantity -= $giftVariantsByProductId[$giftVariant->getId()]->getQuantity();

                if ($quantity < 1) {
                    break;
                }
            }
        } else {
            $giftVariantsByProductId[$productGift->getId()] = new ProductGiftInCart(
                $cartItem->getProduct(),
                $productGift,
                $this->productGiftCalculation->getGiftPrice(),
                $productGift->getAvailableQuantity($quantity)
            );
        }

        return $giftVariantsByProductId;
    }
}
