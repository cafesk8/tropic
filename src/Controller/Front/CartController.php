<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Component\DiscountExclusion\DiscountExclusionFacade;
use App\Component\FlashMessage\ErrorExtractor;
use App\Form\Front\Cart\AddProductFormType;
use App\Form\Front\Cart\CartFormType;
use App\Model\Cart\Cart;
use App\Model\Cart\CartFacade;
use App\Model\Gtm\GtmFacade;
use App\Model\Order\Gift\OrderGiftFacade;
use App\Model\Order\Preview\OrderPreviewFactory;
use App\Model\Pricing\Group\PricingGroupFacade;
use App\Model\Product\Gift\ProductGiftInCartFacade;
use App\Model\Product\Product;
use App\Model\Product\ProductFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Cart\AddProductResult;
use Shopsys\FrameworkBundle\Model\Module\ModuleList;
use Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreview;
use Shopsys\FrameworkBundle\Model\TransportAndPayment\FreeTransportAndPaymentFacade;
use Shopsys\ReadModelBundle\Product\Listed\ListedProductView;
use Shopsys\ReadModelBundle\Product\Listed\ListedProductViewFacadeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CartController extends FrontBaseController
{
    public const AFTER_ADD_WINDOW_ACCESSORIES_LIMIT = 3;

    public const RECALCULATE_ONLY_PARAMETER_NAME = 'recalculateOnly';

    /**
     * @var \App\Model\Product\ProductFacade
     */
    protected $productFacade;

    /**
     * @var \App\Model\Order\Gift\OrderGiftFacade
     */
    protected $orderGiftFacade;

    /**
     * @var \App\Model\Cart\CartFacade
     */
    private $cartFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \App\Model\TransportAndPayment\FreeTransportAndPaymentFacade
     */
    private $freeTransportAndPaymentFacade;

    /**
     * @var \App\Model\Order\Preview\OrderPreviewFactory
     */
    private $orderPreviewFactory;

    /**
     * @var \App\Component\FlashMessage\ErrorExtractor
     */
    private $errorExtractor;

    /**
     * @var \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface
     */
    private $tokenManager;

    /**
     * @var \App\Model\Product\Gift\ProductGiftInCartFacade
     */
    private $productGiftInCartFacade;

    /**
     * @var \App\Model\Gtm\GtmFacade
     */
    private $gtmFacade;

    /**
     * @var \Shopsys\ReadModelBundle\Product\Listed\ListedProductViewFacadeInterface
     */
    private $listedProductViewFacade;

    /**
     * @var \App\Component\DiscountExclusion\DiscountExclusionFacade
     */
    private $discountExclusionFacade;

    /**
     * @var \App\Model\Pricing\Group\PricingGroupFacade
     */
    private $pricingGroupFacade;

    /**
     * @param \App\Model\Cart\CartFacade $cartFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\TransportAndPayment\FreeTransportAndPaymentFacade $freeTransportAndPaymentFacade
     * @param \App\Model\Order\Preview\OrderPreviewFactory $orderPreviewFactory
     * @param \App\Component\FlashMessage\ErrorExtractor $errorExtractor
     * @param \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface $tokenManager
     * @param \App\Model\Product\Gift\ProductGiftInCartFacade $productGiftInCartFacade
     * @param \App\Model\Gtm\GtmFacade $gtmFacade
     * @param \Shopsys\ReadModelBundle\Product\Listed\ListedProductViewFacadeInterface $listedProductViewFacade
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Model\Order\Gift\OrderGiftFacade $orderGiftFacade
     * @param \App\Component\DiscountExclusion\DiscountExclusionFacade $discountExclusionFacade
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     */
    public function __construct(
        CartFacade $cartFacade,
        Domain $domain,
        FreeTransportAndPaymentFacade $freeTransportAndPaymentFacade,
        OrderPreviewFactory $orderPreviewFactory,
        ErrorExtractor $errorExtractor,
        CsrfTokenManagerInterface $tokenManager,
        ProductGiftInCartFacade $productGiftInCartFacade,
        GtmFacade $gtmFacade,
        ListedProductViewFacadeInterface $listedProductViewFacade,
        ProductFacade $productFacade,
        OrderGiftFacade $orderGiftFacade,
        DiscountExclusionFacade $discountExclusionFacade,
        PricingGroupFacade $pricingGroupFacade
    ) {
        $this->cartFacade = $cartFacade;
        $this->domain = $domain;
        $this->freeTransportAndPaymentFacade = $freeTransportAndPaymentFacade;
        $this->orderPreviewFactory = $orderPreviewFactory;
        $this->errorExtractor = $errorExtractor;
        $this->tokenManager = $tokenManager;
        $this->productGiftInCartFacade = $productGiftInCartFacade;
        $this->gtmFacade = $gtmFacade;
        $this->listedProductViewFacade = $listedProductViewFacade;
        $this->productFacade = $productFacade;
        $this->orderGiftFacade = $orderGiftFacade;
        $this->discountExclusionFacade = $discountExclusionFacade;
        $this->pricingGroupFacade = $pricingGroupFacade;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function indexAction(Request $request)
    {
        $cart = $this->cartFacade->findCartOfCurrentCustomerUser();
        $this->correctCartItemQuantitiesByStore($cart);
        $cartItems = $cart === null ? [] : $cart->getItems();
        /** @var \App\Model\Customer\User\CustomerUser|null $customerUser */
        $customerUser = $this->getUser();

        $domainId = $this->domain->getId();

        $cartGiftsByProductId = $this->productGiftInCartFacade->getProductGiftInCartByProductId($cartItems);
        $this->cartFacade->addAllGifts($cartGiftsByProductId);
        $orderPreview = $this->orderPreviewFactory->createForCurrentUser();
        $productsPrice = $orderPreview->getProductsPrice();
        $this->cartFacade->verifySelectedOrderGift($productsPrice->getPriceWithVat(), $domainId, $this->pricingGroupFacade->getCurrentPricingGroup($customerUser));
        $cartFormData = $this->getCartFormData($cartItems, $cartGiftsByProductId, $cart);

        $offeredGifts = $this->orderGiftFacade->getAllListableGiftProductsByTotalProductPrice($productsPrice->getPriceWithVat(), $domainId, $this->pricingGroupFacade->getCurrentPricingGroup($customerUser));

        $form = $this->createForm(CartFormType::class, $cartFormData, [
            'offeredGifts' => $offeredGifts,
        ]);
        $form->handleRequest($request);

        $invalidCart = false;
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->cartFacade->changeQuantities($form->getData()['quantities']);
                $this->cartFacade->setOrderGiftProduct($form->getData()['orderGiftProduct']);

                if (!$request->get(self::RECALCULATE_ONLY_PARAMETER_NAME, false)) {
                    return $this->redirectToRoute('front_order_index');
                }
                return $this->redirectToRoute('front_cart');
            } catch (\Shopsys\FrameworkBundle\Model\Cart\Exception\InvalidQuantityException $ex) {
                $invalidCart = true;
            }
        } elseif ($form->isSubmitted()) {
            $invalidCart = true;
        }

        if ($invalidCart) {
            $this->addErrorFlash(t('Please make sure that you entered right quantity of all items in cart.'));
        }

        $form = $this->createForm(CartFormType::class, $cartFormData, [
            'offeredGifts' => $offeredGifts,
        ]);
        $form->handleRequest($request);

        $quantifiedItemsPrices = $orderPreview->getQuantifiedItemsPrices();
        $this->gtmFacade->onCartPage($orderPreview);

        return $this->render('Front/Content/Cart/index.html.twig', [
            'cart' => $cart,
            'cartItems' => $cartItems,
            'cartItemPrices' => $quantifiedItemsPrices,
            'cartGiftsByProductId' => $cartGiftsByProductId,
            'form' => $form->createView(),
            'cartItemDiscountsIndexedByPromoCodeId' => $orderPreview->getQuantifiedItemsDiscountsIndexedByPromoCodeId(),
            'productsPrice' => $productsPrice,
            'promoCodesIndexedById' => $orderPreview->getPromoCodesIndexedById(),
            'locale' => $this->domain->getLocale(),
            'nextLevelGifts' => $this->orderGiftFacade->getAllListableNextLevelGiftProductsByTotalProductPrice($productsPrice->getPriceWithVat(), $domainId, $this->pricingGroupFacade->getCurrentPricingGroup($customerUser)),
            'nextLevelDifference' => $this->orderGiftFacade->getNextLevelDifference($productsPrice->getPriceWithVat(), $domainId),
            'registrationDiscountExclusionText' => $this->discountExclusionFacade->getRegistrationDiscountExclusionText($this->domain->getId()),
            'promoDiscountExclusionText' => $this->discountExclusionFacade->getPromoDiscountExclusionText($this->domain->getId()),
            'allDiscountExclusionText' => $this->discountExclusionFacade->getAllDiscountExclusionText($this->domain->getId()),
            'quantifiedItemsDiscountsByIndex' => $orderPreview->getQuantifiedItemsDiscounts(),
        ]);
    }

    /**
     * @param \App\Model\Cart\Item\CartItem[] $cartItems
     * @param \App\Model\Product\Gift\ProductGiftInCart[] $productGiftsInCart
     * @param \App\Model\Cart\Cart|null $cart
     * @return mixed[]
     */
    private function getCartFormData(array $cartItems, array $productGiftsInCart, ?Cart $cart = null): array
    {
        $cartFormData = [
            'quantities' => [],
            'chosenGifts' => [],
            'orderGiftProduct' => $cart === null ? null : $cart->getOrderGiftProduct(),
        ];
        foreach ($cartItems as $cartItem) {
            $cartFormData['quantities'][$cartItem->getId()] = $cartItem->getQuantity();
        }

        /** @var \App\Model\Product\Gift\ProductGiftInCart $productGiftInCart */
        foreach ($productGiftsInCart as $productGiftInCart) {
            $cartFormData['chosenGifts'] = array_replace($cartFormData['chosenGifts'], $this->getChosenGiftVariant($productGiftInCart, $cart));
        }

        return $cartFormData;
    }

    /**
     * @param \App\Model\Product\Gift\ProductGiftInCart[] $productGiftInCart
     * @param \App\Model\Cart\Cart|null $cart
     * @return mixed[]
     */
    private function getChosenGiftVariant(array $productGiftInCart, ?Cart $cart = null): array
    {
        $chosenGifts = [];
        foreach ($productGiftInCart as $giftVariantInCart) {
            $chosenGifts[$giftVariantInCart->getProduct()->getId()][$giftVariantInCart->getGift()->getId()] =
                $cart !== null && $cart->isProductGiftSelected($giftVariantInCart->getGift()->getId(), $giftVariantInCart->getProduct()->getId());
        }

        return $chosenGifts;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function boxAction(Request $request): Response
    {
        $orderPreview = $this->orderPreviewFactory->createForCurrentUser();
        $cart = $this->cartFacade->findCartOfCurrentCustomerUser();

        $this->correctCartItemQuantitiesByStore($cart);

        $renderFlashMessage = $request->query->getBoolean('renderFlashMessages', false);

        $productsPrice = $orderPreview->getTotalPrice();

        $domainId = $this->domain->getId();
        $remainingPriceWithVat = $this->freeTransportAndPaymentFacade->getRemainingPriceWithVat(
            $productsPrice->getPriceWithVat(),
            $domainId
        );

        return $this->render('Front/Inline/Cart/cartBox.html.twig', [
            'cart' => $cart,
            'cartItems' => $cart === null ? [] : $cart->getItems(),
            'orderPreview' => $orderPreview,
            'productsPrice' => $productsPrice,
            'renderFlashMessages' => $renderFlashMessage,
            'isFreeTransportAndPaymentActive' => $this->freeTransportAndPaymentFacade->isActive($domainId),
            'isPaymentAndTransportFree' => $this->freeTransportAndPaymentFacade->isFree($productsPrice->getPriceWithVat(), $domainId),
            'remainingPriceWithVat' => $remainingPriceWithVat,
            'percentsForFreeTransportAndPayment' => $this->freeTransportAndPaymentFacade->getPercentsForFreeTransportAndPayment($productsPrice->getPriceWithVat(), $domainId),
        ]);
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param string $type
     * @param bool $disabled
     * @param bool $showAmountInput
     * @param bool $displayVariantSelectButton
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addProductFormAction(Product $product, $type = 'normal', $disabled = false, bool $showAmountInput = true, $displayVariantSelectButton = false)
    {
        $form = $this->createForm(AddProductFormType::class, ['productId' => $product->getId()], [
            'action' => $this->generateUrl('front_cart_add_product'),
            'minimum_amount' => $product->getRealMinimumAmount(),
        ]);

        $hardDisabled = $product->isMainVariant() || $product->getRealStockQuantity() < 1 || $product->getCalculatedSellingDenied();
        if ($hardDisabled === true) {
            $disabled = true;
        }

        return $this->render('Front/Inline/Cart/addProduct.html.twig', [
            'form' => $form->createView(),
            'product' => $product,
            'type' => $type,
            'disabled' => $disabled,
            'hardDisabled' => $hardDisabled === true ? '1' : '0',
            'showAmountInput' => $showAmountInput,
            'displayVariantSelectButton' => $displayVariantSelectButton,
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function addProductAction(Request $request)
    {
        $product = $this->productFacade->getSellableById($request->request->get('add_product_form')['productId']);
        $form = $this->createForm(AddProductFormType::class, [], ['minimum_amount' => $product->getRealMinimumAmount()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $formData = $form->getData();

                $addProductResults = $this->cartFacade->addProduct($product, (int)$formData['quantity']);

                foreach ($addProductResults as $addProductResult) {
                    $this->sendAddProductResultFlashMessage($addProductResult);
                }
            } catch (\Shopsys\FrameworkBundle\Model\Product\Exception\ProductNotFoundException $ex) {
                $this->addErrorFlash(t('Selected product no longer available or doesn\'t exist.'));
            } catch (\Shopsys\FrameworkBundle\Model\Cart\Exception\InvalidQuantityException $ex) {
                $this->addErrorFlash(t('Please enter valid quantity you want to add to cart.'));
            } catch (\App\Model\Cart\Exception\OutOfStockException $ex) {
                $this->addErrorFlash(t('Snažíte se koupit více kusů, než je skladem.'));
            } catch (\Shopsys\FrameworkBundle\Model\Cart\Exception\CartException $ex) {
                $this->addErrorFlash(t('Unable to add product to cart'));
            }
        } else {
            // Form errors list in flash message is temporary solution.
            // We need to determine couse of error when adding product to cart.
            $formErrors = $this->errorExtractor->getAllErrorsAsArray($form, $this->getErrorMessages());
            $this->addErrorFlashTwig(
                t('Unable to add product to cart:<br/><ul><li>{{ errors|raw }}</li></ul>'),
                [
                    'errors' => implode('</li><li>', $formErrors),
                ]
            );
        }

        if ($request->headers->get('referer')) {
            $redirectTo = $request->headers->get('referer');
        } else {
            $redirectTo = $this->generateUrl('front_homepage');
        }

        return $this->redirect($redirectTo);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function addProductAjaxAction(Request $request)
    {
        $product = $this->productFacade->getSellableById($request->request->get('add_product_form')['productId']);
        $form = $this->createForm(AddProductFormType::class, [], ['minimum_amount' => $product->getRealMinimumAmount()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $formData = $form->getData();

                $addProductResults = $this->cartFacade->addProduct($product, (int)$formData['quantity']);

                $accessories = $this->listedProductViewFacade->getAccessories(
                    (int)$formData['productId'],
                    self::AFTER_ADD_WINDOW_ACCESSORIES_LIMIT
                );

                $domainId = $this->domain->getId();

                $orderPreview = $this->orderPreviewFactory->createForCurrentUser();
                $productsPrice = $orderPreview->getProductsPrice();
                $remainingPriceWithVat = $this->freeTransportAndPaymentFacade->getRemainingPriceWithVat($productsPrice->getPriceWithVat(), $domainId);
                $quantifiedItemPricesIndexedByCartItemId = $this->getQuantifiedItemPricesForProductItems($addProductResults, $orderPreview);

                if ($request->request->get('add_product_form')['onlyRefresh']) {
                    return $this->json(['refresh' => true]);
                } else {
                    return $this->render('Front/Inline/Cart/afterAddWindow.html.twig', array_merge([
                        'accessories' => $accessories,
                        'ACCESSORIES_ON_BUY' => ModuleList::ACCESSORIES_ON_BUY,
                        'isFreeTransportAndPaymentActive' => $this->freeTransportAndPaymentFacade->isActive($domainId),
                        'isPaymentAndTransportFree' => $this->freeTransportAndPaymentFacade->isFree($productsPrice->getPriceWithVat(), $domainId),
                        'remainingPriceWithVat' => $remainingPriceWithVat,
                        'percentsForFreeTransportAndPayment' => $this->freeTransportAndPaymentFacade->getPercentsForFreeTransportAndPayment($productsPrice->getPriceWithVat(), $domainId),
                        'quantifiedItemPricesIndexedByCartItemId' => $quantifiedItemPricesIndexedByCartItemId,
                    ], $this->getAddedItemsParameters($addProductResults)));
                }
            } catch (\Shopsys\FrameworkBundle\Model\Product\Exception\ProductNotFoundException $ex) {
                $this->addErrorFlash(t('Selected product no longer available or doesn\'t exist.'));
            } catch (\Shopsys\FrameworkBundle\Model\Cart\Exception\InvalidQuantityException $ex) {
                $this->addErrorFlash(t('Please enter valid quantity you want to add to cart.'));
            } catch (\App\Model\Cart\Exception\OutOfStockException $ex) {
                $this->addErrorFlash(t('Snažíte se koupit více kusů, než je skladem.'));
            } catch (\Shopsys\FrameworkBundle\Model\Cart\Exception\CartException $ex) {
                $this->addErrorFlash(t('Unable to add product to cart'));
            }
        } else {
            // Form errors list in flash message is temporary solution.
            // We need to determine couse of error when adding product to cart.
            $formErrors = $this->errorExtractor->getAllErrorsAsArray($form, $this->getErrorMessages());
            $this->addErrorFlashTwig(
                t('Unable to add product to cart:<br/><ul><li>{{ errors|raw }}</li></ul>'),
                ['errors' => implode('</li><li>', $formErrors)]
            );
        }

        return $this->render('Front/Inline/Cart/afterAddWithErrorWindow.html.twig', [
            'errors' => $this->getErrorMessages(),
        ]);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Cart\AddProductResult[] $addProductResults
     * @param \App\Model\Order\Preview\OrderPreview $orderPreview
     * @return \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice[]
     */
    private function getQuantifiedItemPricesForProductItems(array $addProductResults, OrderPreview $orderPreview): array
    {
        $quantifiedItemsPricesForProductItems = [];
        foreach ($addProductResults as $addProductResult) {
            /** @var \App\Model\Cart\Item\CartItem $addedCartItem */
            $addedCartItem = $addProductResult->getCartItem();
            $addedProduct = $addedCartItem->getProduct();

            $quantifiedItemsPrices = $orderPreview->getQuantifiedItemsPrices();
            foreach ($orderPreview->getQuantifiedProducts() as $index => $quantifiedProduct) {
                if ($quantifiedProduct->getProduct()->getId() === $addedProduct->getId()) {
                    if ($quantifiedProduct->isSaleItem() && $addedCartItem->isSaleItem()
                    || !$quantifiedProduct->isSaleItem() && !$addedCartItem->isSaleItem()) {
                        $quantifiedItemsPricesForProductItems[$addedCartItem->getId()] = $quantifiedItemsPrices[$index];
                    }
                }
            }
        }

        return $quantifiedItemsPricesForProductItems;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Cart\AddProductResult $addProductResult
     */
    private function sendAddProductResultFlashMessage(
        AddProductResult $addProductResult
    ) {
        if ($addProductResult->getIsNew()) {
            $this->addSuccessFlashTwig(
                t('Product <strong>{{ name }}</strong> ({{ quantity|formatNumber }} {{ unitName }}) added to the cart'),
                [
                    'name' => $addProductResult->getCartItem()->getName(),
                    'quantity' => $addProductResult->getAddedQuantity(),
                    'unitName' => $addProductResult->getCartItem()->getProduct()->getUnit()->getName(),
                ]
            );
        } else {
            $this->addSuccessFlashTwig(
                t('Product <strong>{{ name }}</strong> added to the cart (total amount {{ quantity|formatNumber }} {{ unitName }})'),
                [
                    'name' => $addProductResult->getCartItem()->getName(),
                    'quantity' => $addProductResult->getCartItem()->getQuantity(),
                    'unitName' => $addProductResult->getCartItem()->getProduct()->getUnit()->getName(),
                ]
            );
        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int $cartItemId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, int $cartItemId): Response
    {
        $token = new CsrfToken('front_cart_delete_' . $cartItemId, $request->query->get('_token'));

        if ($this->tokenManager->isTokenValid($token)) {
            try {
                $productName = $this->cartFacade->getProductByCartItemId($cartItemId)->getName();

                $this->cartFacade->deleteCartItem($cartItemId);

                $this->addSuccessFlashTwig(
                    t('Product {{ name }} removed from cart'),
                    ['name' => $productName]
                );
            } catch (\Shopsys\FrameworkBundle\Model\Cart\Exception\InvalidCartItemException $ex) {
                $this->addErrorFlash(t('Unable to remove item from cart. The item is probably already removed.'));
            }
        } else {
            $this->addErrorFlash(
                t('Unable to remove item from cart. The link for removing it probably expired, try it again.')
            );
        }

        if ($request->isXmlHttpRequest()) {
            return $this->redirectToRoute('front_cart_box', ['renderFlashMessages' => true]);
        }

        return $this->redirectToRoute('front_cart');
    }

    /**
     * @param \App\Model\Cart\Cart|null $cart
     */
    private function correctCartItemQuantitiesByStore(?Cart $cart): void
    {
        if ($cart !== null) {
            $this->cartFacade->correctCartQuantitiesAccordingToStockedQuantities();
        }
    }

    /**
     * @param \App\Model\Product\View\ListedProductView $listedProductView
     * @param string $type
     * @param bool $showAmountInput
     * @param bool $onlyRefresh
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function productActionAction(ListedProductView $listedProductView, string $type = 'normal', bool $showAmountInput = true, bool $onlyRefresh = false)
    {
        $form = $this->createForm(AddProductFormType::class, ['productId' => $listedProductView->getId()], [
            'action' => $this->generateUrl('front_cart_add_product'),
            'minimum_amount' => $listedProductView->getAction()->getMinimumAmount(),
            'only_refresh' => $onlyRefresh,
        ]);

        return $this->render('Front/Inline/Cart/productAction.html.twig', [
            'form' => $form->createView(),
            'productView' => $listedProductView,
            'type' => $type,
            'showAmountInput' => $showAmountInput,
        ]);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Cart\AddProductResult[] $addProductResults
     * @return array
     */
    private function getAddedItemsParameters(array $addProductResults): array
    {
        $addedItems = [];
        $totalAddedPrice = Money::zero();
        $totalAddedQuantity = 0;
        $addedUnit = null;

        foreach ($addProductResults as $addProductResult) {
            $addedCartItem = $addProductResult->getCartItem();
            $addedItems[] = $addedCartItem;
            $addedItemPrice = $addedCartItem->getWatchedPrice();
            $addedUnit = $addedCartItem->getProduct()->getUnit();
            $addedItemQuantity = $addProductResult->getAddedQuantity();
            if ($addedItemPrice !== null) {
                $totalAddedPrice = $totalAddedPrice->add($addedItemPrice->multiply($addedItemQuantity));
            }
            $totalAddedQuantity += $addedItemQuantity;
        }

        return [
            'addedQuantity' => $totalAddedQuantity,
            'addedPrice' => $totalAddedPrice,
            'addedItems' => $addedItems,
            'addedUnit' => $addedUnit,
        ];
    }
}
