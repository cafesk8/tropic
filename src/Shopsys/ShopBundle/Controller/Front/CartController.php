<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Front;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\FlashMessage\ErrorExtractor;
use Shopsys\FrameworkBundle\Model\Cart\AddProductResult;
use Shopsys\FrameworkBundle\Model\Cart\CartFacade;
use Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer;
use Shopsys\FrameworkBundle\Model\Module\ModuleList;
use Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreviewFactory;
use Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessoryFacade;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\TransportAndPayment\FreeTransportAndPaymentFacade;
use Shopsys\ShopBundle\Form\Front\Cart\AddProductFormType;
use Shopsys\ShopBundle\Form\Front\Cart\CartFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CartController extends FrontBaseController
{
    const AFTER_ADD_WINDOW_ACCESSORIES_LIMIT = 3;

    const RECALCULATE_ONLY_PARAMETER_NAME = 'recalculateOnly';

    /**
     * @var \Shopsys\FrameworkBundle\Model\Cart\CartFacade
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
     * @var \Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreviewFactory
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
     * @param \Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessoryFacade $productAccessoryFacade
     * @param \Shopsys\FrameworkBundle\Model\Cart\CartFacade $cartFacade
     * @param \Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer $currentCustomer
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\TransportAndPayment\FreeTransportAndPaymentFacade $freeTransportAndPaymentFacade
     * @param \Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreviewFactory $orderPreviewFactory
     * @param \Shopsys\FrameworkBundle\Component\FlashMessage\ErrorExtractor $errorExtractor
     * @param \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface $tokenManager
     */
    public function __construct(
        ProductAccessoryFacade $productAccessoryFacade,
        CartFacade $cartFacade,
        CurrentCustomer $currentCustomer,
        Domain $domain,
        FreeTransportAndPaymentFacade $freeTransportAndPaymentFacade,
        OrderPreviewFactory $orderPreviewFactory,
        ErrorExtractor $errorExtractor,
        CsrfTokenManagerInterface $tokenManager
    ) {
        $this->productAccessoryFacade = $productAccessoryFacade;
        $this->cartFacade = $cartFacade;
        $this->currentCustomer = $currentCustomer;
        $this->domain = $domain;
        $this->freeTransportAndPaymentFacade = $freeTransportAndPaymentFacade;
        $this->orderPreviewFactory = $orderPreviewFactory;
        $this->errorExtractor = $errorExtractor;
        $this->tokenManager = $tokenManager;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function indexAction(Request $request)
    {
        $cart = $this->cartFacade->findCartOfCurrentCustomer();
        $cartItems = $cart === null ? [] : $cart->getItems();

        $cartFormData = ['quantities' => []];
        foreach ($cartItems as $cartItem) {
            $cartFormData['quantities'][$cartItem->getId()] = $cartItem->getQuantity();
        }

        $form = $this->createForm(CartFormType::class, $cartFormData);
        $form->handleRequest($request);

        $invalidCart = false;
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->cartFacade->changeQuantities($form->getData()['quantities']);

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
            $this->getFlashMessageSender()->addErrorFlash(
                t('Please make sure that you entered right quantity of all items in cart.')
            );
        }

        $domainId = $this->domain->getId();

        $orderPreview = $this->orderPreviewFactory->createForCurrentUser();
        $productsPrice = $orderPreview->getProductsPrice();
        $remainingPriceWithVat = $this->freeTransportAndPaymentFacade->getRemainingPriceWithVat(
            $productsPrice->getPriceWithVat(),
            $domainId
        );

        return $this->render('@ShopsysShop/Front/Content/Cart/index.html.twig', [
            'cart' => $cart,
            'cartItems' => $cartItems,
            'cartItemPrices' => $orderPreview->getQuantifiedItemsPrices(),
            'form' => $form->createView(),
            'isFreeTransportAndPaymentActive' => $this->freeTransportAndPaymentFacade->isActive($domainId),
            'isPaymentAndTransportFree' => $this->freeTransportAndPaymentFacade->isFree($productsPrice->getPriceWithVat(), $domainId),
            'remainingPriceWithVat' => $remainingPriceWithVat,
            'cartItemDiscounts' => $orderPreview->getQuantifiedItemsDiscounts(),
            'productsPrice' => $productsPrice,
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function boxAction(Request $request): Response
    {
        $orderPreview = $this->orderPreviewFactory->createForCurrentUser();
        $cart = $this->cartFacade->findCartOfCurrentCustomer();
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
            'cartItemPrices' => $orderPreview->getQuantifiedItemsPrices(),
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
     */
    public function addProductFormAction(Product $product, $type = 'normal')
    {
        $form = $this->createForm(AddProductFormType::class, ['productId' => $product->getId()], [
            'action' => $this->generateUrl('front_cart_add_product'),
        ]);

        return $this->render('@ShopsysShop/Front/Inline/Cart/addProduct.html.twig', [
            'form' => $form->createView(),
            'product' => $product,
            'type' => $type,
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

                $addProductResult = $this->cartFacade->addProductToCart($formData['productId'], (int)$formData['quantity']);

                $this->sendAddProductResultFlashMessage($addProductResult);
            } catch (\Shopsys\FrameworkBundle\Model\Product\Exception\ProductNotFoundException $ex) {
                $this->getFlashMessageSender()->addErrorFlash(t('Selected product no longer available or doesn\'t exist.'));
            } catch (\Shopsys\FrameworkBundle\Model\Cart\Exception\InvalidQuantityException $ex) {
                $this->getFlashMessageSender()->addErrorFlash(t('Please enter valid quantity you want to add to cart.'));
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

                $this->sendAddProductResultFlashMessage($addProductResult);

                $accessories = $this->productAccessoryFacade->getTopOfferedAccessories(
                    $addProductResult->getCartItem()->getProduct(),
                    $this->domain->getId(),
                    $this->currentCustomer->getPricingGroup(),
                    self::AFTER_ADD_WINDOW_ACCESSORIES_LIMIT
                );

                return $this->render('@ShopsysShop/Front/Inline/Cart/afterAddWindow.html.twig', [
                    'accessories' => $accessories,
                    'ACCESSORIES_ON_BUY' => ModuleList::ACCESSORIES_ON_BUY,
                ]);
            } catch (\Shopsys\FrameworkBundle\Model\Product\Exception\ProductNotFoundException $ex) {
                $this->getFlashMessageSender()->addErrorFlash(t('Selected product no longer available or doesn\'t exist.'));
            } catch (\Shopsys\FrameworkBundle\Model\Cart\Exception\InvalidQuantityException $ex) {
                $this->getFlashMessageSender()->addErrorFlash(t('Please enter valid quantity you want to add to cart.'));
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

        return $this->forward('ShopsysShopBundle:Front/FlashMessage:index');
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
}
