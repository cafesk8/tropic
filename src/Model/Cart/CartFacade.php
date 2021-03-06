<?php

declare(strict_types=1);

namespace App\Model\Cart;

use App\Component\FlashMessage\FlashMessageSender;
use App\Model\Cart\Exception\OutOfStockException;
use App\Model\Cart\Item\CartItem;
use App\Model\Order\Discount\CurrentOrderDiscountLevelFacade;
use App\Model\Order\Gift\OrderGiftFacade;
use App\Model\Product\Product;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Cart\AddProductResult;
use Shopsys\FrameworkBundle\Model\Cart\CartFacade as BaseCartFacade;
use Shopsys\FrameworkBundle\Model\Cart\CartFactory;
use Shopsys\FrameworkBundle\Model\Cart\CartRepository;
use Shopsys\FrameworkBundle\Model\Cart\Exception\InvalidCartItemException;
use Shopsys\FrameworkBundle\Model\Cart\Item\CartItemFactoryInterface;
use Shopsys\FrameworkBundle\Model\Cart\Watcher\CartWatcherFacade;
use Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserIdentifierFactory;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\CurrentPromoCodeFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
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
 * @method \App\Model\Cart\Cart|null findCartByCustomerUserIdentifier(\Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserIdentifier $customerUserIdentifier)
 */
class CartFacade extends BaseCartFacade
{
    /**
     * @var \App\Model\Order\Gift\OrderGiftFacade
     */
    protected $orderGiftFacade;

    /**
     * @var \App\Model\Order\Discount\CurrentOrderDiscountLevelFacade
     */
    private $currentOrderDiscountLevelFacade;

    /**
     * @var \App\Component\FlashMessage\FlashMessageSender
     */
    private $flashMessageSender;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Cart\CartFactory $cartFactory
     * @param \App\Model\Product\ProductRepository $productRepository
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserIdentifierFactory $customerUserIdentifierFactory
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser $currentCustomerUser
     * @param \App\Model\Order\PromoCode\CurrentPromoCodeFacade $currentPromoCodeFacade
     * @param \App\Model\Product\Pricing\ProductPriceCalculationForCustomerUser $productPriceCalculation
     * @param \App\Model\Cart\Item\CartItemFactory $cartItemFactory
     * @param \Shopsys\FrameworkBundle\Model\Cart\CartRepository $cartRepository
     * @param \Shopsys\FrameworkBundle\Model\Cart\Watcher\CartWatcherFacade $cartWatcherFacade
     * @param \App\Model\Order\Gift\OrderGiftFacade $orderGiftFacade
     * @param \App\Model\Order\Discount\CurrentOrderDiscountLevelFacade $currentOrderDiscountLevelFacade
     * @param \App\Component\FlashMessage\FlashMessageSender $flashMessageSender
     */
    public function __construct(
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
        OrderGiftFacade $orderGiftFacade,
        CurrentOrderDiscountLevelFacade $currentOrderDiscountLevelFacade,
        FlashMessageSender $flashMessageSender
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

        $this->orderGiftFacade = $orderGiftFacade;
        $this->currentOrderDiscountLevelFacade = $currentOrderDiscountLevelFacade;
        $this->flashMessageSender = $flashMessageSender;
    }

    /**
     * @param \App\Model\Cart\Cart|null $cart
     * @return \App\Model\Cart\Cart|null
     */
    public function correctCartQuantitiesAccordingToStockedQuantities(?Cart $cart): ?Cart
    {
        if ($cart === null) {
            return null;
        }
        $cartModifiedQuantitiesIndexedByCartItemId = [];

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

        return $this->findCartOfCurrentCustomerUser();
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
                $this->flashMessageSender->addErrorFlashTwig(t('Do??lo ke zm??n??m v ko????ku. Pros??m, p??ekontrolujte si produkty.'));
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
                $this->flashMessageSender->addErrorFlashTwig(t('Do??lo ke zm??n??m v ko????ku. Pros??m, p??ekontrolujte si produkty.'));
            }
        }

        return $modifyFormData;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param int $quantity
     * @return \Shopsys\FrameworkBundle\Model\Cart\AddProductResult[]
     */
    public function addProduct(Product $product, int $quantity): array
    {
        $cart = $this->getCartOfCurrentCustomerUserCreateIfNotExists();

        $cartItemsByProductId = [];
        try {
            $cartItemsByProductId = $cart->getItemsByProductId($product->getId());
        } catch (InvalidCartItemException $ex) {
        }

        $productQuantityInCart = 0;
        foreach ($cartItemsByProductId as $cartItem) {
            $productQuantityInCart += $cartItem->getQuantity();
        }

        if ($product->isUsingStock() && ($productQuantityInCart + $quantity) > $product->getStockQuantity()) {
            throw new OutOfStockException();
        }

        return $this->addProductItemsToCart($product->getId(), $quantity, $cartItemsByProductId);
    }

    /**
     * Inspired by the parent method addProductToCart
     *
     * @param int $productId
     * @param int $quantity
     * @param \App\Model\Cart\Item\CartItem[] $cartItemsByProductId
     * @return \Shopsys\FrameworkBundle\Model\Cart\AddProductResult[]
     */
    private function addProductItemsToCart($productId, $quantity, array $cartItemsByProductId = []): array
    {
        $addProductResults = [];
        $product = $this->productRepository->getSellableById(
            $productId,
            $this->domain->getId(),
            $this->currentCustomerUser->getPricingGroup()
        );
        $cart = $this->getCartOfCurrentCustomerUserCreateIfNotExists();

        if (!is_int($quantity) || $quantity <= 0) {
            throw new \Shopsys\FrameworkBundle\Model\Cart\Exception\InvalidQuantityException($quantity);
        }

        $remainingQuantityToAdd = $quantity;

        // changing quantity of the existing cart items
        $availableSaleStockQuantity = $product->getRealSaleStocksQuantity();
        $availableNonSaleStockQuantity = $product->getRealNonSaleStocksQuantity();
        foreach ($cartItemsByProductId as $cartItem) {
            if ($cartItem->isSaleItem() && $availableSaleStockQuantity > 0) {
                $currentSaleQuantityInCart = $cartItem->getQuantity();
                $availableSaleStockQuantity -= $currentSaleQuantityInCart;
                $saleQuantityToAdd = $availableSaleStockQuantity;
                if ($saleQuantityToAdd > 0) {
                    if ($saleQuantityToAdd > $remainingQuantityToAdd) {
                        $saleQuantityToAdd = $remainingQuantityToAdd;
                    }
                    $validSaleQuantityToAdd = $this->getValidCartItemQuantity($cartItem, $saleQuantityToAdd);
                    $cartItem->changeQuantity($currentSaleQuantityInCart + $validSaleQuantityToAdd);
                    $remainingQuantityToAdd -= $validSaleQuantityToAdd;
                    $availableSaleStockQuantity -= $validSaleQuantityToAdd;
                    $addProductResults[] = new AddProductResult($cartItem, false, $validSaleQuantityToAdd);
                }
            } elseif ($remainingQuantityToAdd > 0) {
                $currentNonSaleQuantity = $cartItem->getQuantity();
                $availableNonSaleStockQuantity -= $currentNonSaleQuantity;
                if ($remainingQuantityToAdd <= $availableSaleStockQuantity) {
                    continue;
                }
                $nonSaleQuantityToAdd = $remainingQuantityToAdd - $availableSaleStockQuantity;
                if ($nonSaleQuantityToAdd > 0 && $nonSaleQuantityToAdd <= $availableNonSaleStockQuantity) {
                    if ($nonSaleQuantityToAdd > $remainingQuantityToAdd) {
                        $nonSaleQuantityToAdd = $remainingQuantityToAdd;
                    }
                    $validNonSaleQuantityToAdd = $this->getValidCartItemQuantity($cartItem, $nonSaleQuantityToAdd);
                    $cartItem->changeQuantity($currentNonSaleQuantity + $validNonSaleQuantityToAdd);
                    $remainingQuantityToAdd -= $validNonSaleQuantityToAdd;
                    $addProductResults[] = new AddProductResult($cartItem, false, $validNonSaleQuantityToAdd);
                }
            }
            $cartItem->changeAddedAt(new \DateTime());
        }

        // adding new sale cart item
        if ($remainingQuantityToAdd > 0 && $availableSaleStockQuantity > 0) {
            if ($availableSaleStockQuantity <= $remainingQuantityToAdd) {
                $saleCartItemQuantity = $availableSaleStockQuantity;
            } else {
                $saleCartItemQuantity = $remainingQuantityToAdd;
            }
            $saleProductPrice = $this->productPriceCalculation->calculatePriceForCurrentUser($product, true);
            $saleCartItem = $this->cartItemFactory->create($cart, $product, $saleCartItemQuantity, $saleProductPrice->getPriceWithVat(), null, null, true);
            $validSaleCartItemQuantity = $this->getValidCartItemQuantity($saleCartItem, $saleCartItemQuantity);
            $saleCartItem->changeQuantity($validSaleCartItemQuantity);
            $cart->addItem($saleCartItem);
            $this->em->persist($saleCartItem);
            $remainingQuantityToAdd -= $validSaleCartItemQuantity;
            $addProductResults[] = new AddProductResult($saleCartItem, true, $validSaleCartItemQuantity);
        }

        // adding new non-sale item
        if ($remainingQuantityToAdd > 0 && $availableNonSaleStockQuantity > 0) {
            $productPrice = $this->productPriceCalculation->calculatePriceForCurrentUser($product);
            $nonSaleCartItem = $this->cartItemFactory->create($cart, $product, $remainingQuantityToAdd, $productPrice->getPriceWithVat());
            $validNonSaleCartItemQuantity = $this->getValidCartItemQuantity($nonSaleCartItem, $remainingQuantityToAdd);
            $nonSaleCartItem->changeQuantity($validNonSaleCartItemQuantity);
            $cart->addItem($nonSaleCartItem);
            $this->em->persist($nonSaleCartItem);
            $addProductResults[] = new AddProductResult($nonSaleCartItem, true, $validNonSaleCartItemQuantity);
        }

        if (empty($addProductResults)) {
            throw new OutOfStockException();
        }
        $cart->setModifiedNow();
        $this->correctCartQuantitiesAccordingToStockedQuantities($cart);

        $this->em->flush();

        return $addProductResults;
    }

    /**
     * @param \App\Model\Product\Gift\ProductGiftInCart[][] $productGiftInCart
     */
    public function addAllGifts(array $productGiftInCart): void
    {
        $cart = $this->findCartOfCurrentCustomerUser();

        if ($cart === null) {
            return;
        }

        $this->removeAllGifts($cart);

        $cartGifts = $cart->addAllGifts($this->cartItemFactory, $productGiftInCart);
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
        $allRemovedGifts = $cart->removeAllGifts();
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
     * @param \App\Model\Cart\Cart|null $cart
     * @return \App\Model\Cart\Cart|null
     */
    public function checkCartModificationsAndDeleteCartIfEmpty(?Cart $cart): ?Cart
    {
        if ($cart === null) {
            return null;
        }
        $this->cartWatcherFacade->checkCartModifications($cart);
        if ($cart->isEmpty()) {
            $this->deleteCart($cart);
            return null;
        }
        return $this->correctCartQuantitiesAccordingToStockedQuantities($cart);
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
        if ($cartItem->isSaleItem()) {
            $realStockQuantity = $product->getRealSaleStocksQuantity();
        } else {
            $realStockQuantity = $product->getRealNonSaleStocksQuantity();
        }

        if ($product->isUsingStock()) {
            if ($realMinimumAmount > $realStockQuantity) {
                $this->flashMessageSender->addErrorFlash(t('Produkt %name% musel b??t z ko????ku odstran??n, proto??e nen?? skladem.', [
                    '%name%' => $cartItem->getName($this->domain->getLocale()),
                ]));

                return 0;
            }

            if ($desiredQuantity > $realStockQuantity) {
                $this->flashMessageSender->addErrorFlash(t('Polo??ka %name% je skladem k dispozici v po??tu %quantity% ks, po??et kus?? ve Va??em ko????ku jsme proto upravili.', [
                    '%name%' => $cartItem->getName($this->domain->getLocale()),
                    '%quantity%' => $realStockQuantity,
                ]));

                return $realStockQuantity;
            }
        }

        if ($desiredQuantity < $realMinimumAmount) {
            $this->flashMessageSender->addErrorFlash(t('Polo??ku %name% je mo??n?? nakoupit v minim??ln??m po??tu %quantity% ks, po??et kus?? ve Va??em ko????ku jsme proto upravili.', [
                '%name%' => $cartItem->getName($this->domain->getLocale()),
                '%quantity%' => $realMinimumAmount,
            ]));

            return $realMinimumAmount;
        }

        if ($desiredQuantity % $product->getAmountMultiplier() !== 0) {
            $this->flashMessageSender->addErrorFlash(t('Polo??ku %name% je mo??n?? nakoupit po n??sobc??ch %multiplier%, po??et kus?? ve Va??em ko????ku jsme proto upravili.', [
                '%name%' => $cartItem->getName($this->domain->getLocale()),
                '%multiplier%' => $product->getAmountMultiplier(),
            ]));

            return (int)floor($desiredQuantity / $product->getAmountMultiplier()) * $product->getAmountMultiplier();
        }

        return $desiredQuantity;
    }

    /**
     * @param \App\Model\Product\Product|null $product
     */
    public function setOrderGiftProduct(?Product $product): void
    {
        $cart = $this->findCartOfCurrentCustomerUser();
        if ($cart !== null) {
            $cart->setOrderGiftProduct($product);
            $this->em->flush();
        }
    }

    /**
     * @return \App\Model\Product\Product|null
     */
    public function getOrderGiftProduct(): ?Product
    {
        $cart = $this->findCartOfCurrentCustomerUser();
        if ($cart !== null) {
            return $cart->getOrderGiftProduct();
        }

        return null;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $totalProductPriceWithVat
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     */
    public function verifySelectedOrderGift(Money $totalProductPriceWithVat, int $domainId, PricingGroup $pricingGroup): void
    {
        /** @var \App\Model\Cart\Cart|null $cart */
        $cart = $this->findCartOfCurrentCustomerUser();

        if ($cart !== null) {
            $currentOrderGiftProduct = $cart->getOrderGiftProduct();
            $isOrderGiftProductValid = $this->orderGiftFacade->isOrderGiftProductValid($currentOrderGiftProduct, $totalProductPriceWithVat, $domainId, $pricingGroup);

            if ($isOrderGiftProductValid === false) {
                $this->flashMessageSender->addInfoFlashTwig(
                    t('Ji?? nem??te n??rok na vybran?? d??rek k n??kupu s n??zvem <strong>{{ name }}</strong> proto V??m byl odebr??n. Pros??m p??ekontrolujte si objedn??vku'),
                    ['name' => $currentOrderGiftProduct->getName()]
                );
                $this->setOrderGiftProduct(null);
            }
        }
    }

    public function cleanAdditionalData()
    {
        parent::cleanAdditionalData();
        $this->currentOrderDiscountLevelFacade->unsetActiveOrderLevelDiscount();
    }

    /**
     * @deprecated since TF-214, use CartFacade::addProduct instead
     * @param int $productId
     * @param int $quantity
     * @return \Shopsys\FrameworkBundle\Model\Cart\AddProductResult|void
     */
    public function addProductToCart($productId, $quantity)
    {
        @trigger_error('Deprecated, you should use CartFacade::addProduct instead.', E_USER_DEPRECATED);
    }

    /**
     * @return bool
     */
    public function isBulkyTransportRequired(): bool
    {
        $cart = $this->findCartOfCurrentCustomerUser();

        if ($cart === null) {
            return false;
        }

        foreach ($cart->getItems() as $item) {
            if ($item->getProduct()->isBulky()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isOversizedTransportRequired(): bool
    {
        $cart = $this->findCartOfCurrentCustomerUser();

        if ($cart === null) {
            return false;
        }

        foreach ($cart->getItems() as $item) {
            if ($item->getProduct()->isOversized()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function containsForeignSupplierProducts(): bool
    {
        $cart = $this->findCartOfCurrentCustomerUser();

        if ($cart === null) {
            return false;
        }

        foreach ($cart->getItems() as $item) {
            if ($item->getProduct()->isForeignSupplier()) {
                return true;
            }
        }

        return false;
    }
}
