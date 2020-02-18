<?php

declare(strict_types=1);

namespace App\Model\Product\PromoProduct;

use App\Model\Cart\Cart;
use App\Model\Cart\CartFacade;
use App\Model\Customer\User\CustomerUser;
use App\Model\Product\ProductFacade;

class PromoProductInCartFacade
{
    /**
     * @var \App\Model\Product\PromoProduct\PromoProductRepository
     */
    private $promoProductRepository;

    /**
     * @var \App\Model\Cart\CartFacade
     */
    private $cartFacade;

    /**
     * @var \App\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @param \App\Model\Product\PromoProduct\PromoProductRepository $promoProductRepository
     * @param \App\Model\Cart\CartFacade $cartFacade
     * @param \App\Model\Product\ProductFacade $productFacade
     */
    public function __construct(PromoProductRepository $promoProductRepository, CartFacade $cartFacade, ProductFacade $productFacade)
    {
        $this->promoProductRepository = $promoProductRepository;
        $this->cartFacade = $cartFacade;
        $this->productFacade = $productFacade;
    }

    /**
     * @param \App\Model\Cart\Cart $cart
     * @param int $domainId
     * @param \App\Model\Customer\User\CustomerUser|null $customerUser
     * @return \App\Model\Product\PromoProduct\PromoProduct[][]
     */
    public function getPromoProductsForCart(?Cart $cart, int $domainId, ?CustomerUser $customerUser): array
    {
        if ($cart === null) {
            return [];
        }

        $promoProducts = $this->promoProductRepository->getPromoProductsWithMinimalCartPrice(
            $cart->getTotalWatchedPriceOfProducts(),
            $domainId,
            $customerUser
        );

        $promoProductsForCart = [];

        foreach ($promoProducts as $promoProduct) {
            foreach ($promoProduct->getProductsAccordingToVariant() as $product) {
                if ($this->productFacade->isProductMarketable($product) === true) {
                    $promoProductsForCart[$promoProduct->getId()][$product->getId()] = $promoProduct;
                }
            }
        }

        return $promoProductsForCart;
    }

    /**
     * @param \App\Model\Cart\Cart|null $cart
     */
    public function checkMinimalCartPricesForCart(?Cart $cart): void
    {
        if ($cart === null) {
            return;
        }

        $totalWatchedPriceOfProducts = $cart->getTotalWatchedPriceOfProducts()->getAmount();

        foreach ($cart->getPromoProductItems() as $promoProductItem) {
            $promoProduct = $promoProductItem->getPromoProduct();
            if ($promoProduct !== null) {
                $minimalCartPrice = $promoProduct->getMinimalCartPrice();
                if ($minimalCartPrice === null || $minimalCartPrice->getAmount() > $totalWatchedPriceOfProducts) {
                    $this->cartFacade->deleteCartItem($promoProductItem->getId());
                }
            } else {
                $this->cartFacade->deleteCartItem($promoProductItem->getId());
            }
        }
    }
}
