<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Cart;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\FlashMessage\FlashMessageSender;
use Shopsys\FrameworkBundle\Model\Cart\CartFacade as BaseCartFacade;
use Shopsys\FrameworkBundle\Model\Cart\CartFactory;
use Shopsys\FrameworkBundle\Model\Cart\CartRepository;
use Shopsys\FrameworkBundle\Model\Cart\Item\CartItemFactoryInterface;
use Shopsys\FrameworkBundle\Model\Cart\Watcher\CartWatcherFacade;
use Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer;
use Shopsys\FrameworkBundle\Model\Customer\CustomerIdentifier;
use Shopsys\FrameworkBundle\Model\Customer\CustomerIdentifierFactory;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\CurrentPromoCodeFacade;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForUser;
use Shopsys\FrameworkBundle\Model\Product\ProductRepository;
use Shopsys\ShopBundle\Model\Cart\Item\CartItem;

/**
 * @property \Shopsys\ShopBundle\Model\Cart\CartWatcher\CartWatcherFacade $cartWatcherFacade
 */
class CartFacade extends BaseCartFacade
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\FlashMessage\FlashMessageSender
     */
    protected $flashMessageSender;

    /**
     * @param \Shopsys\FrameworkBundle\Component\FlashMessage\FlashMessageSender $flashMessageSender
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Cart\CartFactory $cartFactory
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductRepository $productRepository
     * @param \Shopsys\FrameworkBundle\Model\Customer\CustomerIdentifierFactory $customerIdentifierFactory
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer $currentCustomer
     * @param \Shopsys\FrameworkBundle\Model\Order\PromoCode\CurrentPromoCodeFacade $currentPromoCodeFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForUser $productPriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Cart\Item\CartItemFactoryInterface $cartItemFactory
     * @param \Shopsys\FrameworkBundle\Model\Cart\CartRepository $cartRepository
     * @param \Shopsys\FrameworkBundle\Model\Cart\Watcher\CartWatcherFacade $cartWatcherFacade
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
        CartWatcherFacade $cartWatcherFacade
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
    }

    /**
     * @return int[]
     */
    public function correctCartQuantitiesAccordingToStockedQuantities(): array
    {
        $cartModifiedQuantitiesIndexedByCartItemId = [];

        $cart = $this->findCartOfCurrentCustomer();
        if ($cart === null) {
            return $cartModifiedQuantitiesIndexedByCartItemId;
        }
        foreach ($cart->getItems() as $cartItem) {
            if ($this->canUpdateCartItemQuantity($cartItem, $cartItem->getQuantity()) === true) {
                $cartModifiedQuantitiesIndexedByCartItemId[$cartItem->getId()] = $cartItem->getProduct()->getStockQuantity();
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
            $cartItem = $cart->getItemById($cartItemId);

            if ($this->canUpdateCartItemQuantity($cartItem, (int)$quantity) === true) {
                $correctedCartQuantitiesByCartItemId[$cartItem->getId()] = $cartItem->getProduct()->getStockQuantity();
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
            $cartItem = $cart->getItemById($cartItemId);

            if ($this->canUpdateCartItemQuantity($cartItem, (int)$quantity) === true) {
                $modifyFormData[$cartItem->getId()] = $cartItem->getProduct()->getStockQuantity();
            } else {
                $modifyFormData[$cartItemId] = $quantity;
            }
        }

        return $modifyFormData;
    }

    /**
     * @param int[] $cartModifiedQuantitiesIndexedByCartItemId
     */
    public function displayInfoMessageAboutCorrectedCartItemsQuantities(array $cartModifiedQuantitiesIndexedByCartItemId): void
    {
        if (count($cartModifiedQuantitiesIndexedByCartItemId) === 0) {
            return;
        }

        $cart = $this->findCartOfCurrentCustomer();

        if ($cart === null) {
            return;
        }

        foreach ($cart->getItems() as $cartItem) {
            if (array_key_exists($cartItem->getId(), $cartModifiedQuantitiesIndexedByCartItemId)) {
                $this->flashMessageSender->addErrorFlashTwig(
                    t('Položka {{ name }} je skladem k dispozici v počtu {{ quantity }} ks, počet kusů ve Vašem košíku jsme proto upravili.'),
                    [
                        'name' => $cartItem->getName($this->domain->getLocale()),
                        'quantity' => $cartItem->getProduct()->getStockQuantity(),
                    ]
                );
            }
        }
    }

    /**
     * @param int $productId
     * @param int $quantity
     * @return \Shopsys\FrameworkBundle\Model\Cart\AddProductResult
     */
    public function addProductToCart($productId, $quantity)
    {
        $product = $this->productRepository->getSellableById(
            $productId,
            $this->domain->getId(),
            $this->currentCustomer->getPricingGroup()
        );

        /** @var \Shopsys\ShopBundle\Model\Cart\Cart $cart */
        $cart = $this->getCartOfCurrentCustomerCreateIfNotExists();

        $productQuantityInCart = 0;
        try {
            $cartItemByProductId = $cart->getItemByProductId((int)$productId);
            $productQuantityInCart = $cartItemByProductId->getQuantity();
        } catch (\Shopsys\FrameworkBundle\Model\Cart\Exception\InvalidCartItemException $ex) {
        }

        if ($product->isUsingStock() && ($productQuantityInCart + $quantity) > $product->getStockQuantity()) {
            throw new \Shopsys\ShopBundle\Model\Cart\Exception\OutOfStockException();
        }

        return parent::addProductToCart($productId, $quantity);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Gift\ProductGiftInCart[] $productGiftInCart
     * @param mixed[] $selectedGifts
     * @throws \Shopsys\FrameworkBundle\Model\Cart\Exception\CartIsEmptyException
     */
    public function updateGifts(array $productGiftInCart, array $selectedGifts): void
    {
        /** @var \Shopsys\ShopBundle\Model\Cart\Cart $cart */
        $cart = $this->findCartOfCurrentCustomer();

        if ($cart === null) {
            throw new \Shopsys\FrameworkBundle\Model\Cart\Exception\CartIsEmptyException();
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
        /** @var \Shopsys\ShopBundle\Model\Cart\Cart $cart */
        $cart = $this->findCartOfCurrentCustomer();

        if ($cart === null) {
            return [];
        }

        return $cart->getGifts();
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\CustomerIdentifier $customerIdentifier
     * @return \Shopsys\FrameworkBundle\Model\Cart\Cart|null
     */
    public function findCartByCustomerIdentifier(CustomerIdentifier $customerIdentifier)
    {
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
     * @param \Shopsys\ShopBundle\Model\Cart\Item\CartItem $cartItem
     * @param int $quantity
     * @return bool
     */
    private function canUpdateCartItemQuantity(CartItem $cartItem, int $quantity): bool
    {
        return $cartItem->getProduct()->isUsingStock() && $quantity > $cartItem->getProduct()->getStockQuantity() === true;
    }

    /**
     * @return bool
     */
    public function isEmailTransportCart(): bool
    {
        return $this->cartWatcherFacade->isEmailTransportCart(
            $this->getCartOfCurrentCustomerCreateIfNotExists()
        );
    }
}
