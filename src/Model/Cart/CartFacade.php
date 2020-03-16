<?php

declare(strict_types=1);

namespace App\Model\Cart;

use App\Model\Cart\Exception\OutOfStockException;
use App\Model\Cart\Item\CartItem;
use App\Model\Product\Product;
use App\Model\Product\ProductFacade;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\FlashMessage\FlashMessageSender;
use Shopsys\FrameworkBundle\Model\Cart\AddProductResult;
use Shopsys\FrameworkBundle\Model\Cart\CartFacade as BaseCartFacade;
use Shopsys\FrameworkBundle\Model\Cart\CartFactory;
use Shopsys\FrameworkBundle\Model\Cart\CartRepository;
use Shopsys\FrameworkBundle\Model\Cart\Exception\InvalidCartItemException;
use Shopsys\FrameworkBundle\Model\Cart\Item\CartItemFactoryInterface;
use Shopsys\FrameworkBundle\Model\Cart\Watcher\CartWatcherFacade;
use Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserIdentifier;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserIdentifierFactory;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\CurrentPromoCodeFacade;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForCustomerUser;
use Shopsys\FrameworkBundle\Model\Product\ProductRepository;

/**
 * @property \App\Model\Cart\CartWatcher\CartWatcherFacade $cartWatcherFacade
 * @property \App\Model\Product\ProductRepository $productRepository
 * @property \App\Model\Order\PromoCode\CurrentPromoCodeFacade $currentPromoCodeFacade
 * @property \App\Model\Cart\Item\CartItemFactory $cartItemFactory
 * @method deleteCart(\App\Model\Cart\Cart $cart)
 * @method \App\Model\Product\Product getProductByCartItemId(int $cartItemId)
 * @method \App\Model\Cart\Cart|null findCartOfCurrentCustomerUser()
 * @method \App\Model\Cart\Cart getCartOfCurrentCustomerUserCreateIfNotExists()
 * @method \App\Model\Cart\Cart getCartByCustomerUserIdentifierCreateIfNotExists(\Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserIdentifier $customerUserIdentifier)
 * @property \App\Model\Product\Pricing\ProductPriceCalculationForCustomerUser $productPriceCalculation
 */
class CartFacade extends BaseCartFacade
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\FlashMessage\FlashMessageSender
     */
    protected $flashMessageSender;

    /**
     * @var \App\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Component\FlashMessage\FlashMessageSender $flashMessageSender
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Cart\CartFactory $cartFactory
     * @param \App\Model\Product\ProductRepository $productRepository
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserIdentifierFactory  $customerUserIdentifierFactory
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser $currentCustomerUser
     * @param \App\Model\Order\PromoCode\CurrentPromoCodeFacade $currentPromoCodeFacade
     * @param \App\Model\Product\Pricing\ProductPriceCalculationForCustomerUser $productPriceCalculation
     * @param \App\Model\Cart\Item\CartItemFactory $cartItemFactory
     * @param \Shopsys\FrameworkBundle\Model\Cart\CartRepository $cartRepository
     * @param \Shopsys\FrameworkBundle\Model\Cart\Watcher\CartWatcherFacade $cartWatcherFacade
     * @param \App\Model\Product\ProductFacade $productFacade
     */
    public function __construct(
        FlashMessageSender $flashMessageSender,
        EntityManagerInterface $em,
        CartFactory $cartFactory,
        ProductRepository $productRepository,
        CustomerUserIdentifierFactory $customerUserIdentifierFactory,
        Domain $domain,
        CurrentCustomerUser $currentCustomerUser,
        CurrentPromoCodeFacade $currentPromoCodeFacade,
        ProductPriceCalculationForCustomerUser $productPriceCalculation,
        CartItemFactoryInterface $cartItemFactory,
        CartRepository $cartRepository,
        CartWatcherFacade $cartWatcherFacade,
        ProductFacade $productFacade
    ) {
        parent::__construct(
            $em,
            $cartFactory,
            $productRepository,
            $customerUserIdentifierFactory,
            $domain,
            $currentCustomerUser,
            $currentPromoCodeFacade,
            $productPriceCalculation,
            $cartItemFactory,
            $cartRepository,
            $cartWatcherFacade
        );

        $this->flashMessageSender = $flashMessageSender;
        $this->productFacade = $productFacade;
    }

    /**
     * @return array
     */
    public function correctCartQuantitiesAccordingToStockedQuantities(): array
    {
        $cartModifiedQuantitiesIndexedByCartItemId = [];

        $cart = $this->findCartOfCurrentCustomerUser();

        foreach ($cart->getItems() as $cartItem) {
            $newCartItemQuantity = $this->getValidCartItemQuantity($cartItem);

            if ($newCartItemQuantity === 0) {
                $this->deleteCartItem($cartItem->getId());
            } elseif ($newCartItemQuantity !== $cartItem->getQuantity()) {
                $cartModifiedQuantitiesIndexedByCartItemId[$cartItem->getId()] = $newCartItemQuantity;
            }
        }

        if (count($cartModifiedQuantitiesIndexedByCartItemId) > 0) {
            $this->changeQuantities($cartModifiedQuantitiesIndexedByCartItemId);
        }

        return $cartModifiedQuantitiesIndexedByCartItemId;
    }

    /**
     * @param int[] $cartFormDataQuantities
     * @return int[]
     */
    public function getChangedCartQuantitiesBySentData(array $cartFormDataQuantities): array
    {
        $correctedCartQuantitiesByCartItemId = [];

        $cart = $this->findCartOfCurrentCustomerUser();

        if ($cart === null) {
            return $correctedCartQuantitiesByCartItemId;
        }
        foreach ($cartFormDataQuantities as $cartItemId => $quantity) {
            try {
                $cartItem = $cart->getItemById($cartItemId);
                $newCartItemQuantity = $this->getValidCartItemQuantity($cartItem, (int)$quantity);

                if ($newCartItemQuantity !== $quantity) {
                    $correctedCartQuantitiesByCartItemId[$cartItem->getId()] = $newCartItemQuantity;
                }
            } catch (InvalidCartItemException $exception) {
                $this->flashMessageSender->addErrorFlashTwig(t('Došlo ke změnám v košíku. Prosím, překontrolujte si produkty.'));
            }
        }

        return $correctedCartQuantitiesByCartItemId;
    }

    /**
     * @param int[] $cartFormDataQuantities
     * @return int[]
     */
    public function getCorrectedQuantitiesBySentData(array $cartFormDataQuantities): array
    {
        $cart = $this->findCartOfCurrentCustomerUser();

        if ($cart === null) {
            return $cartFormDataQuantities;
        }

        $modifyFormData = [];

        foreach ($cartFormDataQuantities as $cartItemId => $quantity) {
            try {
                $cartItem = $cart->getItemById($cartItemId);
                $modifyFormData[$cartItemId] = $this->getValidCartItemQuantity($cartItem, (int)$quantity);
            } catch (InvalidCartItemException $exception) {
                $this->flashMessageSender->addErrorFlashTwig(t('Došlo ke změnám v košíku. Prosím, překontrolujte si produkty.'));
            }
        }

        return $modifyFormData;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param int $quantity
     * @return \Shopsys\FrameworkBundle\Model\Cart\AddProductResult
     */
    public function addProduct(Product $product, int $quantity): AddProductResult
    {
        $cart = $this->getCartOfCurrentCustomerUserCreateIfNotExists();

        $productQuantityInCart = 0;

        try {
            $cartItemByProductId = $cart->getItemByProductId($product->getId());
            $productQuantityInCart = $cartItemByProductId->getQuantity();
        } catch (InvalidCartItemException $ex) {
        }

        if ($product->isUsingStock() && ($productQuantityInCart + $quantity) > $product->getStockQuantity()) {
            throw new OutOfStockException();
        }

        return parent::addProductToCart($product->getId(), $quantity);
    }

    /**
     * @param \App\Model\Product\Gift\ProductGiftInCart[] $productGiftInCart
     * @param mixed[] $selectedGifts
     */
    public function updateGifts(array $productGiftInCart, array $selectedGifts): void
    {
        $cart = $this->findCartOfCurrentCustomerUser();

        if ($cart === null) {
            return;
        }

        $this->removeAllGifts($cart);

        $cartGifts = $cart->updateGifts($this->cartItemFactory, $productGiftInCart, $selectedGifts);
        foreach ($cartGifts as $cartGift) {
            $this->em->persist($cartGift);
        }

        $this->em->flush();
    }

    /**
     * @param \App\Model\Cart\Cart $cart
     */
    private function removeAllGifts(Cart $cart): void
    {
        $allRemovedGifts = $cart->removeAllGift();
        foreach ($allRemovedGifts as $removedGift) {
            $this->em->remove($removedGift);
        }
        $this->em->flush();
    }

    /**
     * @return \App\Model\Cart\Item\CartItem[]
     */
    public function getGifts(): array
    {
        $cart = $this->findCartOfCurrentCustomerUser();

        if ($cart === null) {
            return [];
        }

        return $cart->getGifts();
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserIdentifier  $customerUserIdentifier
     * @return \App\Model\Cart\Cart|null
     */
    public function findCartByCustomerUserIdentifier(CustomerUserIdentifier $customerUserIdentifier)
    {
        /** @var \App\Model\Cart\Cart|null $cart */
        $cart = $this->cartRepository->findByCustomerUserIdentifier($customerUserIdentifier);

        if ($cart !== null) {
            $this->cartWatcherFacade->checkCartModifications($cart, $customerUserIdentifier->getCustomerUser());

            if ($cart->isEmpty()) {
                $this->deleteCart($cart);

                return null;
            }
        }

        return $cart;
    }

    /**
     * @return \App\Model\Cart\Item\CartItem[]
     */
    public function getPromoProducts(): array
    {
        $cart = $this->findCartOfCurrentCustomerUser();

        if ($cart === null) {
            return [];
        }

        return $cart->getPromoProductItems();
    }

    /**
     * @param \App\Model\Product\PromoProduct\PromoProduct[] $promoProductsInCart
     * @param mixed[] $selectedPromoProducts
     */
    public function updatePromoProducts(array $promoProductsInCart, array $selectedPromoProducts): void
    {
        $cart = $this->findCartOfCurrentCustomerUser();

        if ($cart === null) {
            return;
        }

        $this->removeAllPromoProductsItems($cart);

        $promoProductItems = $cart->updatePromoProductsItems(
            $this->cartItemFactory,
            $this->productFacade,
            $promoProductsInCart,
            $selectedPromoProducts
        );
        foreach ($promoProductItems as $promoProductItem) {
            $this->em->persist($promoProductItem);
        }

        $this->em->flush();
    }

    /**
     * @param \App\Model\Cart\Cart $cart
     */
    private function removeAllPromoProductsItems(Cart $cart): void
    {
        $allRemovedPromoProductItems = $cart->removeAllPromoProductsAndGetThem();
        foreach ($allRemovedPromoProductItems as $removedPromoProductItem) {
            $this->em->remove($removedPromoProductItem);
        }
        $this->em->flush();
    }

    /**
     * @return bool
     */
    public function showEmailTransportInCart(): bool
    {
        $cart = $this->getCartOfCurrentCustomerUserCreateIfNotExists();

        foreach ($cart->getItems() as $cartItem) {
            if (!$cartItem->getProduct()->isGiftCertificate()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    public function showOnlyGiftCertificatePaymentsInCart(): bool
    {
        $cart = $this->getCartOfCurrentCustomerUserCreateIfNotExists();

        foreach ($cart->getItems() as $cartItem) {
            if ($cartItem->getProduct()->isGiftCertificate()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \App\Model\Cart\Item\CartItem $cartItem
     * @param int|null $quantity
     * @return int
     */
    private function getValidCartItemQuantity(CartItem $cartItem, ?int $quantity = null): int
    {
        $desiredQuantity = $quantity ?? $cartItem->getQuantity();
        $product = $cartItem->getProduct();
        $realMinimumAmount = $product->getRealMinimumAmount();
        $realStockQuantity = $product->getRealStockQuantity();

        if ($product->isUsingStock()) {
            if ($realMinimumAmount > $realStockQuantity) {
                $this->flashMessageSender->addErrorFlash(t('Produkt %name% musel být z košíku odstraněn, protože není skladem.', [
                    '%name%' => $cartItem->getName($this->domain->getLocale()),
                ]));

                return 0;
            }

            if ($desiredQuantity > $realStockQuantity) {
                $this->flashMessageSender->addErrorFlash(t('Položka %name% je skladem k dispozici v počtu %quantity% ks, počet kusů ve Vašem košíku jsme proto upravili.', [
                    '%name%' => $cartItem->getName($this->domain->getLocale()),
                    '%quantity%' => $realStockQuantity,
                ]));

                return $realStockQuantity;
            }
        }

        if ($desiredQuantity < $realMinimumAmount) {
            $this->flashMessageSender->addErrorFlash(t('Položku %name% je možné nakoupit v minimálním počtu %quantity% ks, počet kusů ve Vašem košíku jsme proto upravili.', [
                '%name%' => $cartItem->getName($this->domain->getLocale()),
                '%quantity%' => $realMinimumAmount,
            ]));

            return $realMinimumAmount;
        }

        if ($desiredQuantity % $product->getAmountMultiplier() !== 0) {
            $this->flashMessageSender->addErrorFlash(t('Položku %name% je možné nakoupit po násobcích %multiplier%, počet kusů ve Vašem košíku jsme proto upravili.', [
                '%name%' => $cartItem->getName($this->domain->getLocale()),
                '%multiplier%' => $product->getAmountMultiplier(),
            ]));

            return (int)floor($desiredQuantity / $product->getAmountMultiplier()) * $product->getAmountMultiplier();
        }

        return $desiredQuantity;
    }
}
