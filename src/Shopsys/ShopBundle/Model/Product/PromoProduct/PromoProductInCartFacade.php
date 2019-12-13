<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\PromoProduct;

use Shopsys\ShopBundle\Model\Cart\Cart;
use Shopsys\ShopBundle\Model\Cart\CartFacade;
use Shopsys\ShopBundle\Model\Customer\User;
use Shopsys\ShopBundle\Model\Product\ProductFacade;

class PromoProductInCartFacade
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProductRepository
     */
    private $promoProductRepository;

    /**
     * @var \Shopsys\ShopBundle\Model\Cart\CartFacade
     */
    private $cartFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProductRepository $promoProductRepository
     * @param \Shopsys\ShopBundle\Model\Cart\CartFacade $cartFacade
     * @param \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade
     */
    public function __construct(PromoProductRepository $promoProductRepository, CartFacade $cartFacade, ProductFacade $productFacade)
    {
        $this->promoProductRepository = $promoProductRepository;
        $this->cartFacade = $cartFacade;
        $this->productFacade = $productFacade;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Cart\Cart $cart
     * @param int $domainId
     * @param \Shopsys\ShopBundle\Model\Customer\User|null $user
     * @return \Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProduct[]
     */
    public function getPromoProductsForCart(?Cart $cart, int $domainId, ?User $user): array
    {
        if ($cart === null) {
            return [];
        }

        $promoProducts = $this->promoProductRepository->getPromoProductsWithMinimalCartPrice(
            $cart->getTotalWatchedPriceOfProducts(),
            $domainId,
            $user
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
     * @param \Shopsys\ShopBundle\Model\Cart\Cart|null $cart
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
