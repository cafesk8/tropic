<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Cart;

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
use Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer;
use Shopsys\FrameworkBundle\Model\Customer\CustomerIdentifier;
use Shopsys\FrameworkBundle\Model\Customer\CustomerIdentifierFactory;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\CurrentPromoCodeFacade;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForUser;
use Shopsys\FrameworkBundle\Model\Product\ProductRepository;
use Shopsys\ShopBundle\Model\Cart\Exception\OutOfStockException;
use Shopsys\ShopBundle\Model\Cart\Item\CartItem;
use Shopsys\ShopBundle\Model\Product\Product;
use Shopsys\ShopBundle\Model\Product\ProductFacade;

/**
 * @property \Shopsys\ShopBundle\Model\Cart\CartWatcher\CartWatcherFacade $cartWatcherFacade
 * @property \Shopsys\ShopBundle\Model\Product\ProductRepository $productRepository
 * @property \Shopsys\ShopBundle\Model\Order\PromoCode\CurrentPromoCodeFacade $currentPromoCodeFacade
 * @property \Shopsys\ShopBundle\Model\Cart\Item\CartItemFactory $cartItemFactory
 * @method deleteCart(\Shopsys\ShopBundle\Model\Cart\Cart $cart)
 * @method \Shopsys\ShopBundle\Model\Product\Product getProductByCartItemId(int $cartItemId)
 * @method \Shopsys\ShopBundle\Model\Cart\Cart|null findCartOfCurrentCustomer()
 * @method \Shopsys\ShopBundle\Model\Cart\Cart getCartByCustomerIdentifierCreateIfNotExists(\Shopsys\FrameworkBundle\Model\Customer\CustomerIdentifier $customerIdentifier)
 * @method \Shopsys\ShopBundle\Model\Cart\Cart getCartOfCurrentCustomerCreateIfNotExists()
 */
class CartFacade extends BaseCartFacade
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\FlashMessage\FlashMessageSender
     */
    protected $flashMessageSender;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Component\FlashMessage\FlashMessageSender $flashMessageSender
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Cart\CartFactory $cartFactory
     * @param \Shopsys\ShopBundle\Model\Product\ProductRepository $productRepository
     * @param \Shopsys\FrameworkBundle\Model\Customer\CustomerIdentifierFactory $customerIdentifierFactory
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer $currentCustomer
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\CurrentPromoCodeFacade $currentPromoCodeFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForUser $productPriceCalculation
     * @param \Shopsys\ShopBundle\Model\Cart\Item\CartItemFactory $cartItemFactory
     * @param \Shopsys\FrameworkBundle\Model\Cart\CartRepository $cartRepository
     * @param \Shopsys\FrameworkBundle\Model\Cart\Watcher\CartWatcherFacade $cartWatcherFacade
     * @param \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade
     */
    public function __construct(
        FlashMessageSender $flashMessageSender,
        EntityManagerInterface $em,
        CartFactory $cartFactory,
        ProductRepository $productRepository,
        CustomerIdentifierFactory $customerIdentifierFactory,
        Domain $domain,
        CurrentCustomer $currentCustomer,
        CurrentPromoCodeFacade $currentPromoCodeFacade,
        ProductPriceCalculationForUser $productPriceCalculation,
        CartItemFactoryInterface $cartItemFactory,
        CartRepository $cartRepository,
        CartWatcherFacade $cartWatcherFacade,
        ProductFacade $productFacade
    ) {
        parent::__construct(
            $em,
            $cartFactory,
            $productRepository,
            $customerIdentifierFactory,
            $domain,
            $currentCustomer,
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

        $cart = $this->findCartOfCurrentCustomer();

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

        $cart = $this->findCartOfCurrentCustomer();

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
        $cart = $this->findCartOfCurrentCustomer();

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
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param int $quantity
     * @return \Shopsys\FrameworkBundle\Model\Cart\AddProductResult
     */
    public function addProduct(Product $product, int $quantity): AddProductResult
    {
        $cart = $this->getCartOfCurrentCustomerCreateIfNotExists();

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
     * @param \Shopsys\ShopBundle\Model\Product\Gift\ProductGiftInCart[] $productGiftInCart
     * @param mixed[] $selectedGifts
     */
    public function updateGifts(array $productGiftInCart, array $selectedGifts): void
    {
        $cart = $this->findCartOfCurrentCustomer();

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
     * @param \Shopsys\ShopBundle\Model\Cart\Cart $cart
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
     * @return \Shopsys\ShopBundle\Model\Cart\Item\CartItem[]
     */
    public function getGifts(): array
    {
        $cart = $this->findCartOfCurrentCustomer();

        if ($cart === null) {
            return [];
        }

        return $cart->getGifts();
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\CustomerIdentifier $customerIdentifier
     * @return \Shopsys\ShopBundle\Model\Cart\Cart|null
     */
    public function findCartByCustomerIdentifier(CustomerIdentifier $customerIdentifier)
    {
        /** @var \Shopsys\ShopBundle\Model\Cart\Cart|null $cart */
        $cart = $this->cartRepository->findByCustomerIdentifier($customerIdentifier);

        if ($cart !== null) {
            $this->cartWatcherFacade->checkCartModifications($cart, $customerIdentifier->getUser());

            if ($cart->isEmpty()) {
                $this->deleteCart($cart);

                return null;
            }
        }

        return $cart;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Cart\Item\CartItem[]
     */
    public function getPromoProducts(): array
    {
        $cart = $this->findCartOfCurrentCustomer();

        if ($cart === null) {
            return [];
        }

        return $cart->getPromoProductItems();
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProduct[] $promoProductsInCart
     * @param mixed[] $selectedPromoProducts
     */
    public function updatePromoProducts(array $promoProductsInCart, array $selectedPromoProducts): void
    {
        $cart = $this->findCartOfCurrentCustomer();

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
     * @param \Shopsys\ShopBundle\Model\Cart\Cart $cart
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
        $cart = $this->getCartOfCurrentCustomerCreateIfNotExists();

        foreach ($cart->getItems() as $cartItem) {
            if ($cartItem->getProduct()->isProductTypeGiftCertificate() === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Cart\Item\CartItem $cartItem
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
