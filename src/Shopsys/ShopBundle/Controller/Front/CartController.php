<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Front;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\FlashMessage\ErrorExtractor;
use Shopsys\FrameworkBundle\Model\Cart\AddProductResult;
use Shopsys\FrameworkBundle\Model\Cart\Item\CartItem;
use Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer;
use Shopsys\FrameworkBundle\Model\Module\ModuleList;
use Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice;
use Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreview;
use Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessoryFacade;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\TopProduct\TopProductFacade;
use Shopsys\FrameworkBundle\Model\TransportAndPayment\FreeTransportAndPaymentFacade;
use Shopsys\ShopBundle\Form\Front\Cart\AddProductFormType;
use Shopsys\ShopBundle\Form\Front\Cart\CartFormType;
use Shopsys\ShopBundle\Model\Cart\Cart;
use Shopsys\ShopBundle\Model\Cart\CartFacade;
use Shopsys\ShopBundle\Model\Gtm\GtmFacade;
use Shopsys\ShopBundle\Model\Order\Preview\OrderPreviewFactory;
use Shopsys\ShopBundle\Model\Product\Gift\ProductGiftFacade;
use Shopsys\ShopBundle\Model\Product\ProductOnCurrentDomainElasticFacade;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CartController extends FrontBaseController
{
    public const AFTER_ADD_WINDOW_ACCESSORIES_LIMIT = 3;

    public const RECALCULATE_ONLY_PARAMETER_NAME = 'recalculateOnly';

    /**
     * @var \Shopsys\ShopBundle\Model\Cart\CartFacade
     */
    private $cartFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer
     */
    private $currentCustomer;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessoryFacade
     */
    private $productAccessoryFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\TransportAndPayment\FreeTransportAndPaymentFacade
     */
    private $freeTransportAndPaymentFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Order\Preview\OrderPreviewFactory
     */
    private $orderPreviewFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Component\FlashMessage\ErrorExtractor
     */
    private $errorExtractor;

    /**
     * @var \Symfony\Component\Security\Csrf\CsrfTokenManager
     */
    private $tokenManager;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Gift\ProductGiftFacade
     */
    private $productGiftFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\TopProduct\TopProductFacade
     */
    private $topProductFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductOnCurrentDomainElasticFacade
     */
    private $productOnCurrentDomainElasticFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Gtm\GtmFacade
     */
    private $gtmFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessoryFacade $productAccessoryFacade
     * @param \Shopsys\ShopBundle\Model\Cart\CartFacade $cartFacade
     * @param \Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer $currentCustomer
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\TransportAndPayment\FreeTransportAndPaymentFacade $freeTransportAndPaymentFacade
     * @param \Shopsys\ShopBundle\Model\Order\Preview\OrderPreviewFactory $orderPreviewFactory
     * @param \Shopsys\FrameworkBundle\Component\FlashMessage\ErrorExtractor $errorExtractor
     * @param \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface $tokenManager
     * @param \Shopsys\ShopBundle\Model\Product\Gift\ProductGiftFacade $productGiftFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\TopProduct\TopProductFacade $topProductFacade
     * @param \Shopsys\ShopBundle\Model\Product\ProductOnCurrentDomainElasticFacade $productOnCurrentDomainElasticFacade
     * @param \Shopsys\ShopBundle\Model\Gtm\GtmFacade $gtmFacade
     */
    public function __construct(
        ProductAccessoryFacade $productAccessoryFacade,
        CartFacade $cartFacade,
        CurrentCustomer $currentCustomer,
        Domain $domain,
        FreeTransportAndPaymentFacade $freeTransportAndPaymentFacade,
        OrderPreviewFactory $orderPreviewFactory,
        ErrorExtractor $errorExtractor,
        CsrfTokenManagerInterface $tokenManager,
        ProductGiftFacade $productGiftFacade,
        TopProductFacade $topProductFacade,
        ProductOnCurrentDomainElasticFacade $productOnCurrentDomainElasticFacade,
        GtmFacade $gtmFacade
    ) {
        $this->productAccessoryFacade = $productAccessoryFacade;
        $this->cartFacade = $cartFacade;
        $this->currentCustomer = $currentCustomer;
        $this->domain = $domain;
        $this->freeTransportAndPaymentFacade = $freeTransportAndPaymentFacade;
        $this->orderPreviewFactory = $orderPreviewFactory;
        $this->errorExtractor = $errorExtractor;
        $this->tokenManager = $tokenManager;
        $this->productGiftFacade = $productGiftFacade;
        $this->topProductFacade = $topProductFacade;
        $this->productOnCurrentDomainElasticFacade = $productOnCurrentDomainElasticFacade;
        $this->gtmFacade = $gtmFacade;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function indexAction(Request $request)
    {
        /** @var \Shopsys\ShopBundle\Model\Cart\Cart $cart */
        $cart = $this->cartFacade->findCartOfCurrentCustomer();
        $this->correctCartItemQuantitiesByStore($cart);
        $cartItems = $cart === null ? [] : $cart->getItems();
        $cartGiftsByProductId = $this->productGiftFacade->getProductGiftInCartByProductId($cartItems);

        $cartFormData = $this->getCartFormData($cartItems, $cartGiftsByProductId, $cart);

        $form = $this->createForm(CartFormType::class, $cartFormData);
        $form->handleRequest($request);

        $invalidCart = false;
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->cartFacade->changeQuantities($form->getData()['quantities']);
                $cartGiftsByProductId = $this->productGiftFacade->getProductGiftInCartByProductId($cart->getItems());
                $this->cartFacade->updateGifts($cartGiftsByProductId, $form->getData()['chosenGifts']);

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

        $domainId = $this->domain->getId();

        /** @var \Shopsys\ShopBundle\Model\Order\Preview\OrderPreview $orderPreview */
        $orderPreview = $this->orderPreviewFactory->createForCurrentUser();
        $productsAndGiftsTotalPrice = $orderPreview->getProductsAndGiftsTotalPrice();
        $remainingPriceWithVat = $this->freeTransportAndPaymentFacade->getRemainingPriceWithVat($productsAndGiftsTotalPrice->getPriceWithVat(), $domainId);
        $topProducts = $this->topProductFacade->getAllOfferedProducts($this->domain->getId(), $this->currentCustomer->getPricingGroup());

        $this->gtmFacade->onCartPage($orderPreview);
        return $this->render('@ShopsysShop/Front/Content/Cart/index.html.twig', [
            'cart' => $cart,
            'cartItems' => $cartItems,
            'cartItemPrices' => $orderPreview->getQuantifiedItemsPrices(),
            'cartGiftsByProductId' => $cartGiftsByProductId,
            'form' => $form->createView(),
            'isFreeTransportAndPaymentActive' => $this->freeTransportAndPaymentFacade->isActive($domainId),
            'isPaymentAndTransportFree' => $this->freeTransportAndPaymentFacade->isFree($productsAndGiftsTotalPrice->getPriceWithVat(), $domainId),
            'remainingPriceWithVat' => $remainingPriceWithVat,
            'cartItemDiscounts' => $orderPreview->getQuantifiedItemsDiscounts(),
            'productsPrice' => $productsAndGiftsTotalPrice,
            'percentsForFreeTransportAndPayment' => $this->freeTransportAndPaymentFacade->getPercentsForFreeTransportAndPayment($productsAndGiftsTotalPrice->getPriceWithVat(), $domainId),
            'promoCode' => $orderPreview->getPromoCode(),
            'topProducts' => $topProducts,
            'variantsIndexedByMainVariantId' => $this->productOnCurrentDomainElasticFacade->getVariantsIndexedByMainVariantId($topProducts),
            'locale' => $this->domain->getLocale(),
        ]);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Cart\Item\CartItem[] $cartItems
     * @param \Shopsys\ShopBundle\Model\Product\Gift\ProductGiftInCart[] $productGiftsInCart
     * @param \Shopsys\ShopBundle\Model\Cart\Cart|null $cart
     * @return mixed[]
     */
    private function getCartFormData(array $cartItems, array $productGiftsInCart, ?Cart $cart = null): array
    {
        $cartFormData = ['quantities' => [], 'chosenGifts' => []];
        foreach ($cartItems as $cartItem) {
            $cartFormData['quantities'][$cartItem->getId()] = $cartItem->getQuantity();
        }

        /** @var \Shopsys\ShopBundle\Model\Product\Gift\ProductGiftInCart $productGiftInCart */
        foreach ($productGiftsInCart as $productGiftInCart) {
            $cartFormData['chosenGifts'] = array_replace($cartFormData['chosenGifts'], $this->getChosenGiftVariant($productGiftInCart, $cart));
        }

        return $cartFormData;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Gift\ProductGiftInCart[] $productGiftInCart
     * @param \Shopsys\ShopBundle\Model\Cart\Cart|null $cart
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
        $cart = $this->cartFacade->findCartOfCurrentCustomer();

        $this->correctCartItemQuantitiesByStore($cart);

        $renderFlashMessage = $request->query->getBoolean('renderFlashMessages', false);

        $productsPrice = $orderPreview->getProductsPrice();

        $domainId = $this->domain->getId();
        $remainingPriceWithVat = $this->freeTransportAndPaymentFacade->getRemainingPriceWithVat(
            $productsPrice->getPriceWithVat(),
            $domainId
        );

        return $this->render('@ShopsysShop/Front/Inline/Cart/cartBox.html.twig', [
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
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @param string $type
     * @param bool $disabled
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addProductFormAction(Product $product, $type = 'normal', $disabled = false)
    {
        $form = $this->createForm(AddProductFormType::class, ['productId' => $product->getId()], [
            'action' => $this->generateUrl('front_cart_add_product'),
        ]);

        $disabled = $disabled || $product->getStockQuantity() < 1;

        return $this->render('@ShopsysShop/Front/Inline/Cart/addProduct.html.twig', [
            'form' => $form->createView(),
            'product' => $product,
            'type' => $type,
            'disabled' => $disabled,
            'hardDisabled' => $disabled ? '1' : '0',
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function addProductAction(Request $request)
    {
        $form = $this->createForm(AddProductFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $formData = $form->getData();

                $addProductResult = $this->cartFacade->addProductToCart((int)$formData['productId'], (int)$formData['quantity']);

                $this->sendAddProductResultFlashMessage($addProductResult);
            } catch (\Shopsys\FrameworkBundle\Model\Product\Exception\ProductNotFoundException $ex) {
                $this->getFlashMessageSender()->addErrorFlash(t('Selected product no longer available or doesn\'t exist.'));
            } catch (\Shopsys\FrameworkBundle\Model\Cart\Exception\InvalidQuantityException $ex) {
                $this->getFlashMessageSender()->addErrorFlash(t('Please enter valid quantity you want to add to cart.'));
            } catch (\Shopsys\ShopBundle\Model\Cart\Exception\OutOfStockException $ex) {
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
        $form = $this->createForm(AddProductFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $formData = $form->getData();

                $addProductResult = $this->cartFacade->addProductToCart($formData['productId'], (int)$formData['quantity']);

                $accessories = $this->productAccessoryFacade->getTopOfferedAccessories(
                    $addProductResult->getCartItem()->getProduct(),
                    $this->domain->getId(),
                    $this->currentCustomer->getPricingGroup(),
                    self::AFTER_ADD_WINDOW_ACCESSORIES_LIMIT
                );

                $domainId = $this->domain->getId();

                $orderPreview = $this->orderPreviewFactory->createForCurrentUser();
                $productsPrice = $orderPreview->getProductsPrice();
                $remainingPriceWithVat = $this->freeTransportAndPaymentFacade->getRemainingPriceWithVat($productsPrice->getPriceWithVat(), $domainId);

                return $this->render('@ShopsysShop/Front/Inline/Cart/afterAddWindow.html.twig', [
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
            } catch (\Shopsys\ShopBundle\Model\Cart\Exception\OutOfStockException $ex) {
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
        return $this->render('@ShopsysShop/Front/Inline/Cart/afterAddWithErrorWindow.html.twig', [
            'errors' => $flashMessageBag->getErrorMessages(),
        ]);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Cart\Item\CartItem $cartItem
     * @param \Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreview $orderPreview
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
     * @param \Shopsys\ShopBundle\Model\Cart\Cart|null $cart
     */
    private function correctCartItemQuantitiesByStore(?Cart $cart): void
    {
        if ($cart !== null) {
            $cartModifiedQuantitiesIndexedByCartItemId = $this->cartFacade->correctCartQuantitiesAccordingToStockedQuantities();
            $this->cartFacade->displayInfoMessageAboutCorrectedCartItemsQuantities($cartModifiedQuantitiesIndexedByCartItemId);
        }
    }
}
