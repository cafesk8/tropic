<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Form\Front\Cart\AddProductFormType;
use App\Form\Front\Cart\CartFormType;
use App\Model\Cart\Cart;
use App\Model\Cart\CartFacade;
use App\Model\Gtm\GtmFacade;
use App\Model\Order\Preview\OrderPreviewFactory;
use App\Model\Order\PromoCode\CurrentPromoCodeFacade;
use App\Model\Product\Gift\ProductGiftInCartFacade;
use App\Model\Product\ProductFacade;
use App\Model\Product\PromoProduct\PromoProductInCartFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\FlashMessage\ErrorExtractor;
use Shopsys\FrameworkBundle\Model\Cart\AddProductResult;
use Shopsys\FrameworkBundle\Model\Cart\Item\CartItem;
use Shopsys\FrameworkBundle\Model\Module\ModuleList;
use Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice;
use Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreview;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\TransportAndPayment\FreeTransportAndPaymentFacade;
use Shopsys\ReadModelBundle\Product\Action\ProductActionView;
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
     * @var \Shopsys\FrameworkBundle\Component\FlashMessage\ErrorExtractor
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
     * @var \App\Model\Order\PromoCode\CurrentPromoCodeFacade
     */
    private $currentPromoCodeFacade;

    /**
     * @var \App\Model\Product\PromoProduct\PromoProductInCartFacade
     */
    private $promoProductInCartFacade;

    /**
     * @var \Shopsys\ReadModelBundle\Product\Listed\ListedProductViewFacadeInterface
     */
    private $listedProductViewFacade;

    /**
     * @param \App\Model\Cart\CartFacade $cartFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\TransportAndPayment\FreeTransportAndPaymentFacade $freeTransportAndPaymentFacade
     * @param \App\Model\Order\Preview\OrderPreviewFactory $orderPreviewFactory
     * @param \Shopsys\FrameworkBundle\Component\FlashMessage\ErrorExtractor $errorExtractor
     * @param \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface $tokenManager
     * @param \App\Model\Product\Gift\ProductGiftInCartFacade $productGiftInCartFacade
     * @param \App\Model\Gtm\GtmFacade $gtmFacade
     * @param \App\Model\Order\PromoCode\CurrentPromoCodeFacade $currentPromoCodeFacade
     * @param \App\Model\Product\PromoProduct\PromoProductInCartFacade $promoProductInCartFacade
     * @param \Shopsys\ReadModelBundle\Product\Listed\ListedProductViewFacadeInterface $listedProductViewFacade
     * @param \App\Model\Product\ProductFacade $productFacade
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
        CurrentPromoCodeFacade $currentPromoCodeFacade,
        PromoProductInCartFacade $promoProductInCartFacade,
        ListedProductViewFacadeInterface $listedProductViewFacade,
        ProductFacade $productFacade
    ) {
        $this->cartFacade = $cartFacade;
        $this->domain = $domain;
        $this->freeTransportAndPaymentFacade = $freeTransportAndPaymentFacade;
        $this->orderPreviewFactory = $orderPreviewFactory;
        $this->errorExtractor = $errorExtractor;
        $this->tokenManager = $tokenManager;
        $this->productGiftInCartFacade = $productGiftInCartFacade;
        $this->gtmFacade = $gtmFacade;
        $this->currentPromoCodeFacade = $currentPromoCodeFacade;
        $this->promoProductInCartFacade = $promoProductInCartFacade;
        $this->listedProductViewFacade = $listedProductViewFacade;
        $this->productFacade = $productFacade;
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
        $promoProductsForCart = $this->promoProductInCartFacade->getPromoProductsForCart($cart, $domainId, $customerUser);

        $cartFormData = $this->getCartFormData($cartItems, $cartGiftsByProductId, $promoProductsForCart, $cart);

        $form = $this->createForm(CartFormType::class, $cartFormData);
        $form->handleRequest($request);

        $this->promoProductInCartFacade->checkMinimalCartPricesForCart($cart);

        $invalidCart = false;
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->cartFacade->changeQuantities($form->getData()['quantities']);

                $cartGiftsByProductId = $this->productGiftInCartFacade->getProductGiftInCartByProductId($cart->getItems());
                $this->cartFacade->updateGifts($cartGiftsByProductId, $form->getData()['chosenGifts']);

                $promoProductsForCart = $this->promoProductInCartFacade->getPromoProductsForCart($cart, $domainId, $customerUser);
                $this->cartFacade->updatePromoProducts($promoProductsForCart, $form->getData()['chosenPromoProducts']);

                $this->promoProductInCartFacade->checkMinimalCartPricesForCart($cart);

                if (!$request->get(self::RECALCULATE_ONLY_PARAMETER_NAME, false)) {
                    return $this->redirectToRoute('front_order_index');
                }
            } catch (\Shopsys\FrameworkBundle\Model\Cart\Exception\InvalidQuantityException $ex) {
                $invalidCart = true;
            }
        } elseif ($form->isSubmitted()) {
            $invalidCart = true;
        }

        if ($invalidCart) {
            $this->getFlashMessageSender()->addErrorFlash(t('Please make sure that you entered right quantity of all items in cart.'));
        }

        $promoProductsForCart = $this->promoProductInCartFacade->getPromoProductsForCart($cart, $domainId, $customerUser);
        $cartFormData = $this->setCartFormDataPromoProducts($cartFormData, $promoProductsForCart, $cart);
        $form = $this->createForm(CartFormType::class, $cartFormData);
        $form->handleRequest($request);

        /** @var \App\Model\Order\Preview\OrderPreview $orderPreview */
        $orderPreview = $this->orderPreviewFactory->createForCurrentUser();
        $productsPrice = $orderPreview->getProductsPrice();
        $remainingPriceWithVat = $this->freeTransportAndPaymentFacade->getRemainingPriceWithVat($productsPrice->getPriceWithVat(), $domainId);
        $topProducts = $this->listedProductViewFacade->getAllTop();
        $quantifiedItemsPrices = $orderPreview->getQuantifiedItemsPrices();
        $this->currentPromoCodeFacade->checkProductActionPriceType($quantifiedItemsPrices);
        $this->gtmFacade->onCartPage($orderPreview);
        return $this->render('Front/Content/Cart/index.html.twig', [
            'cart' => $cart,
            'cartItems' => $cartItems,
            'cartItemPrices' => $quantifiedItemsPrices,
            'cartGiftsByProductId' => $cartGiftsByProductId,
            'promoProducts' => $promoProductsForCart,
            'form' => $form->createView(),
            'isFreeTransportAndPaymentActive' => $this->freeTransportAndPaymentFacade->isActive($domainId),
            'isPaymentAndTransportFree' => $this->freeTransportAndPaymentFacade->isFree($productsPrice->getPriceWithVat(), $domainId),
            'remainingPriceWithVat' => $remainingPriceWithVat,
            'cartItemDiscountsIndexedByPromoCodeId' => $orderPreview->getQuantifiedItemsDiscountsIndexedByPromoCodeId(),
            'productsPrice' => $productsPrice,
            'percentsForFreeTransportAndPayment' => $this->freeTransportAndPaymentFacade->getPercentsForFreeTransportAndPayment($productsPrice->getPriceWithVat(), $domainId),
            'promoCodesIndexedById' => $orderPreview->getPromoCodesIndexedById(),
            'topProducts' => $topProducts,
            'locale' => $this->domain->getLocale(),
        ]);
    }

    /**
     * @param \App\Model\Cart\Item\CartItem[] $cartItems
     * @param \App\Model\Product\Gift\ProductGiftInCart[] $productGiftsInCart
     * @param \App\Model\Product\PromoProduct\PromoProduct[] $promoProductsForCart
     * @param \App\Model\Cart\Cart|null $cart
     * @return mixed[]
     */
    private function getCartFormData(array $cartItems, array $productGiftsInCart, array $promoProductsForCart, ?Cart $cart = null): array
    {
        $cartFormData = [
            'quantities' => [],
            'chosenGifts' => [],
            'chosenPromoProducts' => [],
        ];
        foreach ($cartItems as $cartItem) {
            $cartFormData['quantities'][$cartItem->getId()] = $cartItem->getQuantity();
        }

        /** @var \App\Model\Product\Gift\ProductGiftInCart $productGiftInCart */
        foreach ($productGiftsInCart as $productGiftInCart) {
            $cartFormData['chosenGifts'] = array_replace($cartFormData['chosenGifts'], $this->getChosenGiftVariant($productGiftInCart, $cart));
        }

        return $this->setCartFormDataPromoProducts($cartFormData, $promoProductsForCart, $cart);
    }

    /**
     * @param array $cartFormData
     * @param \App\Model\Product\PromoProduct\PromoProduct[] $promoProductsForCart
     * @param \App\Model\Cart\Cart|null $cart
     * @return mixed[]
     */
    private function setCartFormDataPromoProducts($cartFormData, array $promoProductsForCart, ?Cart $cart = null): array
    {
        $cartFormData['chosenPromoProducts'] = array_replace($cartFormData['chosenPromoProducts'], $this->getChosenPromoProducts($promoProductsForCart, $cart));

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
     * @param \App\Model\Product\PromoProduct\PromoProduct[][] $promoProductsInCart
     * @param \App\Model\Cart\Cart|null $cart
     * @return mixed[]
     */
    private function getChosenPromoProducts(array $promoProductsInCart, ?Cart $cart = null): array
    {
        $chosenPromoProducts = [];
        foreach ($promoProductsInCart as $promoProductId => $promoProductByProductId) {
            foreach ($promoProductByProductId as $productId => $promoProduct) {
                $chosenPromoProducts[$promoProductId][$productId] =
                    $cart !== null && $cart->isPromoProductSelected($promoProduct, $productId);
            }
        }

        return $chosenPromoProducts;
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

        $productsPrice = $orderPreview->getProductsPrice();

        $domainId = $this->domain->getId();
        $remainingPriceWithVat = $this->freeTransportAndPaymentFacade->getRemainingPriceWithVat(
            $productsPrice->getPriceWithVat(),
            $domainId
        );

        return $this->render('Front/Inline/Cart/cartBox.html.twig', [
            'cart' => $cart,
            'cartItems' => $cart === null ? [] : $cart->getItems(),
            'quantifiedItemsPrices' => $orderPreview->getQuantifiedItemsPrices(),
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
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addProductFormAction(Product $product, $type = 'normal', $disabled = false)
    {
        $form = $this->createForm(AddProductFormType::class, ['productId' => $product->getId()], [
            'action' => $this->generateUrl('front_cart_add_product'),
            'minimum_amount' => $product->getRealMinimumAmount(),
        ]);

        $hardDisabled = $product->getStockQuantity() < 1;
        if ($hardDisabled === true) {
            $disabled = true;
        }

        return $this->render('Front/Inline/Cart/addProduct.html.twig', [
            'form' => $form->createView(),
            'product' => $product,
            'type' => $type,
            'disabled' => $disabled,
            'hardDisabled' => $hardDisabled === true ? '1' : '0',
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

                $addProductResult = $this->cartFacade->addProduct($product, (int)$formData['quantity']);

                $this->sendAddProductResultFlashMessage($addProductResult);
            } catch (\Shopsys\FrameworkBundle\Model\Product\Exception\ProductNotFoundException $ex) {
                $this->getFlashMessageSender()->addErrorFlash(t('Selected product no longer available or doesn\'t exist.'));
            } catch (\Shopsys\FrameworkBundle\Model\Cart\Exception\InvalidQuantityException $ex) {
                $this->getFlashMessageSender()->addErrorFlash(t('Please enter valid quantity you want to add to cart.'));
            } catch (\App\Model\Cart\Exception\OutOfStockException $ex) {
                $this->getFlashMessageSender()->addErrorFlash(t('Snažíte se koupit více kusů, než je skladem.'));
            } catch (\Shopsys\FrameworkBundle\Model\Cart\Exception\CartException $ex) {
                $this->getFlashMessageSender()->addErrorFlash(t('Unable to add product to cart'));
            }
        } else {
            // Form errors list in flash message is temporary solution.
            // We need to determine couse of error when adding product to cart.
            $flashMessageBag = $this->get('shopsys.shop.component.flash_message.bag.front');
            $formErrors = $this->errorExtractor->getAllErrorsAsArray($form, $flashMessageBag);
            $this->getFlashMessageSender()->addErrorFlashTwig(
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

                $addProductResult = $this->cartFacade->addProduct($product, (int)$formData['quantity']);

                $accessories = $this->listedProductViewFacade->getAccessories(
                    (int)$formData['productId'],
                    self::AFTER_ADD_WINDOW_ACCESSORIES_LIMIT
                );

                $domainId = $this->domain->getId();

                $orderPreview = $this->orderPreviewFactory->createForCurrentUser();
                $productsPrice = $orderPreview->getProductsPrice();
                $remainingPriceWithVat = $this->freeTransportAndPaymentFacade->getRemainingPriceWithVat($productsPrice->getPriceWithVat(), $domainId);

                return $this->render('Front/Inline/Cart/afterAddWindow.html.twig', [
                    'accessories' => $accessories,
                    'ACCESSORIES_ON_BUY' => ModuleList::ACCESSORIES_ON_BUY,
                    'cartItem' => $addProductResult->getCartItem(),
                    'isFreeTransportAndPaymentActive' => $this->freeTransportAndPaymentFacade->isActive($domainId),
                    'isPaymentAndTransportFree' => $this->freeTransportAndPaymentFacade->isFree($productsPrice->getPriceWithVat(), $domainId),
                    'remainingPriceWithVat' => $remainingPriceWithVat,
                    'percentsForFreeTransportAndPayment' => $this->freeTransportAndPaymentFacade->getPercentsForFreeTransportAndPayment($productsPrice->getPriceWithVat(), $domainId),
                    'quantifiedItemPrice' => $this->findQuantifiedItemPriceForProduct($addProductResult->getCartItem(), $orderPreview),
                ]);
            } catch (\Shopsys\FrameworkBundle\Model\Product\Exception\ProductNotFoundException $ex) {
                $this->getFlashMessageSender()->addErrorFlash(t('Selected product no longer available or doesn\'t exist.'));
            } catch (\Shopsys\FrameworkBundle\Model\Cart\Exception\InvalidQuantityException $ex) {
                $this->getFlashMessageSender()->addErrorFlash(t('Please enter valid quantity you want to add to cart.'));
            } catch (\App\Model\Cart\Exception\OutOfStockException $ex) {
                $this->getFlashMessageSender()->addErrorFlash(t('Snažíte se koupit více kusů, než je skladem.'));
            } catch (\Shopsys\FrameworkBundle\Model\Cart\Exception\CartException $ex) {
                $this->getFlashMessageSender()->addErrorFlash(t('Unable to add product to cart'));
            }
        } else {
            // Form errors list in flash message is temporary solution.
            // We need to determine couse of error when adding product to cart.
            $flashMessageBag = $this->get('shopsys.shop.component.flash_message.bag.front');
            $formErrors = $this->errorExtractor->getAllErrorsAsArray($form, $flashMessageBag);
            $this->getFlashMessageSender()->addErrorFlashTwig(
                t('Unable to add product to cart:<br/><ul><li>{{ errors|raw }}</li></ul>'),
                ['errors' => implode('</li><li>', $formErrors)]
            );
        }

        $flashMessageBag = $this->get('shopsys.shop.component.flash_message.bag.front');
        return $this->render('Front/Inline/Cart/afterAddWithErrorWindow.html.twig', [
            'errors' => $flashMessageBag->getErrorMessages(),
        ]);
    }

    /**
     * @param \App\Model\Cart\Item\CartItem $cartItem
     * @param \App\Model\Order\Preview\OrderPreview $orderPreview
     * @return \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice|null
     */
    private function findQuantifiedItemPriceForProduct(CartItem $cartItem, OrderPreview $orderPreview): ?QuantifiedItemPrice
    {
        $quantifiedProductIndex = null;
        foreach ($orderPreview->getQuantifiedProducts() as $index => $quantifiedProduct) {
            if ($quantifiedProduct->getProduct()->getId() === $cartItem->getProduct()->getId()) {
                $quantifiedProductIndex = $index;
                break;
            }
        }

        $quantifiedItemsPrices = $orderPreview->getQuantifiedItemsPrices();

        if ($quantifiedProductIndex === null && array_key_exists($quantifiedProductIndex, $quantifiedItemsPrices) === false) {
            return null;
        }

        return $quantifiedItemsPrices[$quantifiedProductIndex];
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Cart\AddProductResult $addProductResult
     */
    private function sendAddProductResultFlashMessage(
        AddProductResult $addProductResult
    ) {
        if ($addProductResult->getIsNew()) {
            $this->getFlashMessageSender()->addSuccessFlashTwig(
                t('Product <strong>{{ name }}</strong> ({{ quantity|formatNumber }} {{ unitName }}) added to the cart'),
                [
                    'name' => $addProductResult->getCartItem()->getName(),
                    'quantity' => $addProductResult->getAddedQuantity(),
                    'unitName' => $addProductResult->getCartItem()->getProduct()->getUnit()->getName(),
                ]
            );
        } else {
            $this->getFlashMessageSender()->addSuccessFlashTwig(
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

                $this->getFlashMessageSender()->addSuccessFlashTwig(
                    t('Product {{ name }} removed from cart'),
                    ['name' => $productName]
                );
            } catch (\Shopsys\FrameworkBundle\Model\Cart\Exception\InvalidCartItemException $ex) {
                $this->getFlashMessageSender()->addErrorFlash(t('Unable to remove item from cart. The item is probably already removed.'));
            }
        } else {
            $this->getFlashMessageSender()->addErrorFlash(
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
     * @param \Shopsys\ReadModelBundle\Product\Action\ProductActionView $productActionView
     * @param string $type
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function productActionAction(ProductActionView $productActionView, string $type = 'normal')
    {
        $form = $this->createForm(AddProductFormType::class, ['productId' => $productActionView->getId()], [
             'action' => $this->generateUrl('front_cart_add_product'),
         ]);

        return $this->render('Front/Inline/Cart/productAction.html.twig', [
             'form' => $form->createView(),
             'productActionView' => $productActionView,
             'type' => $type,
         ]);
    }
}
