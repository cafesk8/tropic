<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Component\Cofidis\CofidisFacade;
use App\Component\Cofidis\Exception\CofidisException;
use App\Form\Front\Customer\Password\NewPasswordFormType;
use App\Form\Front\Order\DomainAwareOrderFlowFactory;
use App\Form\Front\Order\OrderFlow;
use App\Form\Front\Order\PaymentFormType;
use App\Model\Blog\Article\BlogArticleFacade;
use App\Model\Cart\Exception\OutOfStockException;
use App\Model\Country\CountryFacade;
use App\Model\Customer\User\CustomerUserFacade;
use App\Model\Customer\User\CustomerUserUpdateDataFactory;
use App\Model\GoPay\BankSwift\GoPayBankSwift;
use App\Model\GoPay\BankSwift\GoPayBankSwiftFacade;
use App\Model\GoPay\Exception\GoPayNotConfiguredException;
use App\Model\GoPay\Exception\GoPayPaymentDownloadException;
use App\Model\GoPay\GoPayFacadeOnCurrentDomain;
use App\Model\GoPay\GoPayTransactionFacade;
use App\Model\GoPay\PaymentMethod\GoPayPaymentMethod;
use App\Model\Gtm\GtmFacade;
use App\Model\Heureka\HeurekaReviewFacade;
use App\Model\Order\FrontOrderData;
use App\Model\Order\Item\OrderItemDataFactory;
use App\Model\Order\Item\OrderItemFactory;
use App\Model\Order\Order;
use App\Model\Order\OrderData;
use App\Model\Order\OrderDataFactory;
use App\Model\Order\OrderDataMapper;
use App\Model\Order\Preview\OrderPreviewFactory;
use App\Model\PayPal\PayPalFacade;
use App\Model\Security\CustomerLoginHandler;
use App\Model\Store\Store;
use App\Model\Store\StoreFacade;
use App\Model\Transport\PickupPlace\PacketaPickupPlaceData;
use App\Model\TransportAndPayment\FreeTransportAndPaymentFacade;
use Exception;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\HttpFoundation\DownloadFileResponse;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Cart\CartFacade;
use Shopsys\FrameworkBundle\Model\Customer\Mail\CustomerMailFacade;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser;
use Shopsys\FrameworkBundle\Model\LegalConditions\LegalConditionsFacade;
use Shopsys\FrameworkBundle\Model\Mail\Exception\MailException;
use Shopsys\FrameworkBundle\Model\Newsletter\NewsletterFacade;
use Shopsys\FrameworkBundle\Model\Order\Exception\OrderNotFoundException;
use Shopsys\FrameworkBundle\Model\Order\Mail\OrderMailFacade;
use Shopsys\FrameworkBundle\Model\Order\Order as BaseOrder;
use Shopsys\FrameworkBundle\Model\Order\OrderFacade;
use Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreview;
use Shopsys\FrameworkBundle\Model\Order\Watcher\TransportAndPaymentWatcher;
use Shopsys\FrameworkBundle\Model\Payment\Payment;
use Shopsys\FrameworkBundle\Model\Payment\PaymentFacade;
use Shopsys\FrameworkBundle\Model\Payment\PaymentPriceCalculation;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade;
use Shopsys\FrameworkBundle\Model\Security\Authenticator;
use Shopsys\FrameworkBundle\Model\Security\Roles;
use Shopsys\FrameworkBundle\Model\Transport\TransportFacade;
use Shopsys\FrameworkBundle\Model\Transport\TransportPriceCalculation;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class OrderController extends FrontBaseController
{
    public const SESSION_CREATED_ORDER = 'created_order_id';
    public const SESSION_GOPAY_CHOOSEN_SWIFT = 'gopay_choosen_swift';
    private const HOMEPAGE_ARTICLES_LIMIT = 2;

    /**
     * @var \App\Form\Front\Order\DomainAwareOrderFlowFactory
     */
    private $domainAwareOrderFlowFactory;

    /**
     * @var \App\Model\Cart\CartFacade
     */
    private $cartFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Order\Mail\OrderMailFacade
     */
    private $orderMailFacade;

    /**
     * @var \App\Model\Order\OrderDataMapper
     */
    private $orderDataMapper;

    /**
     * @var \App\Model\Order\OrderFacade
     */
    private $orderFacade;

    /**
     * @var \App\Model\Order\Preview\OrderPreviewFactory
     */
    private $orderPreviewFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Order\Watcher\TransportAndPaymentWatcher
     */
    private $transportAndPaymentWatcher;

    /**
     * @var \App\Model\Payment\PaymentFacade
     */
    private $paymentFacade;

    /**
     * @var \App\Model\Payment\PaymentPriceCalculation
     */
    private $paymentPriceCalculation;

    /**
     * @var \App\Model\Pricing\Currency\CurrencyFacade
     */
    private $currencyFacade;

    /**
     * @var \App\Model\Transport\TransportFacade
     */
    private $transportFacade;

    /**
     * @var \App\Model\Transport\TransportPriceCalculation
     */
    private $transportPriceCalculation;

    /**
     * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
     */
    private $session;

    /**
     * @var \Shopsys\FrameworkBundle\Model\LegalConditions\LegalConditionsFacade
     */
    private $legalConditionsFacade;

    /**
     * @var \App\Model\Newsletter\NewsletterFacade
     */
    private $newsletterFacade;

    /**
     * @var \App\Model\GoPay\BankSwift\GoPayBankSwiftFacade
     */
    private $goPayBankSwiftFacade;

    /**
     * @var \App\Model\GoPay\GoPayFacadeOnCurrentDomain
     */
    private $goPayFacadeOnCurrentDomain;

    /**
     * @var \App\Model\PayPal\PayPalFacade
     */
    private $payPalFacade;

    /**
     * @var \App\Model\Country\CountryFacade
     */
    private $countryFacade;

    /**
     * @var \App\Model\Blog\Article\BlogArticleFacade
     */
    private $blogArticleFacade;

    /**
     * @var \App\Model\Gtm\GtmFacade
     */
    private $gtmFacade;

    /**
     * @var \App\Model\Customer\User\CustomerUserFacade
     */
    private $customerUserFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Security\Authenticator
     */
    private $authenticator;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\Mail\CustomerMailFacade
     */
    private $customerMailFacade;

    /**
     * @var \App\Model\Customer\User\CustomerUserUpdateDataFactory
     */
    private $customerUserUpdateDataFactory;

    /**
     * @var \App\Model\GoPay\GoPayTransactionFacade
     */
    private $goPayTransactionFacade;

    /**
     * @var \App\Model\Order\OrderDataFactory
     */
    private $orderDataFactory;

    /**
     * @var \App\Model\Order\Item\OrderItemFactory
     */
    private $orderItemFactory;

    /**
     * @var \App\Model\Order\Item\OrderItemDataFactory
     */
    private $orderItemDataFactory;

    /**
     * @var \App\Model\Store\StoreFacade
     */
    private $storeFacade;

    /**
     * @var \App\Model\TransportAndPayment\FreeTransportAndPaymentFacade
     */
    private FreeTransportAndPaymentFacade $freeTransportAndPaymentFacade;

    private CofidisFacade $cofidisFacade;

    /**
     * @var \App\Model\Heureka\HeurekaReviewFacade
     */
    private $heurekaReviewFacade;

    /**
     * @param \App\Model\Order\OrderFacade $orderFacade
     * @param \App\Model\Cart\CartFacade $cartFacade
     * @param \App\Model\Order\Preview\OrderPreviewFactory $orderPreviewFactory
     * @param \App\Model\Transport\TransportPriceCalculation $transportPriceCalculation
     * @param \App\Model\Payment\PaymentPriceCalculation $paymentPriceCalculation
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Transport\TransportFacade $transportFacade
     * @param \App\Model\Payment\PaymentFacade $paymentFacade
     * @param \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     * @param \App\Model\Order\OrderDataMapper $orderDataMapper
     * @param \App\Form\Front\Order\DomainAwareOrderFlowFactory $domainAwareOrderFlowFactory
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * @param \Shopsys\FrameworkBundle\Model\Order\Watcher\TransportAndPaymentWatcher $transportAndPaymentWatcher
     * @param \Shopsys\FrameworkBundle\Model\Order\Mail\OrderMailFacade $orderMailFacade
     * @param \Shopsys\FrameworkBundle\Model\LegalConditions\LegalConditionsFacade $legalConditionsFacade
     * @param \App\Model\Newsletter\NewsletterFacade $newsletterFacade
     * @param \App\Model\GoPay\BankSwift\GoPayBankSwiftFacade $goPayBankSwiftFacade
     * @param \App\Model\GoPay\GoPayFacadeOnCurrentDomain $goPayFacadeOnCurrentDomain
     * @param \App\Model\PayPal\PayPalFacade $payPalFacade
     * @param \App\Model\Country\CountryFacade $countryFacade
     * @param \App\Model\Blog\Article\BlogArticleFacade $blogArticleFacade
     * @param \App\Model\Gtm\GtmFacade $gtmFacade
     * @param \App\Model\Customer\User\CustomerUserFacade $customerUserFacade
     * @param \Shopsys\FrameworkBundle\Model\Security\Authenticator $authenticator
     * @param \Shopsys\FrameworkBundle\Model\Customer\Mail\CustomerMailFacade $customerMailFacade
     * @param \App\Model\Customer\User\CustomerUserUpdateDataFactory $customerUserUpdateDataFactory
     * @param \App\Model\GoPay\GoPayTransactionFacade $goPayTransactionFacade
     * @param \App\Model\Order\OrderDataFactory $orderDataFactory
     * @param \App\Model\Order\Item\OrderItemFactory $orderItemFactory
     * @param \App\Model\Order\Item\OrderItemDataFactory $orderItemDataFactory
     * @param \App\Model\TransportAndPayment\FreeTransportAndPaymentFacade $freeTransportAndPaymentFacade
     * @param \App\Component\Cofidis\CofidisFacade $cofidisFacade
     * @param \App\Model\Heureka\HeurekaReviewFacade $heurekaReviewFacade
     * @param \App\Model\Store\StoreFacade $storeFacade
     */
    public function __construct(
        OrderFacade $orderFacade,
        CartFacade $cartFacade,
        OrderPreviewFactory $orderPreviewFactory,
        TransportPriceCalculation $transportPriceCalculation,
        PaymentPriceCalculation $paymentPriceCalculation,
        Domain $domain,
        TransportFacade $transportFacade,
        PaymentFacade $paymentFacade,
        CurrencyFacade $currencyFacade,
        OrderDataMapper $orderDataMapper,
        DomainAwareOrderFlowFactory $domainAwareOrderFlowFactory,
        SessionInterface $session,
        TransportAndPaymentWatcher $transportAndPaymentWatcher,
        OrderMailFacade $orderMailFacade,
        LegalConditionsFacade $legalConditionsFacade,
        NewsletterFacade $newsletterFacade,
        GoPayBankSwiftFacade $goPayBankSwiftFacade,
        GoPayFacadeOnCurrentDomain $goPayFacadeOnCurrentDomain,
        PayPalFacade $payPalFacade,
        CountryFacade $countryFacade,
        BlogArticleFacade $blogArticleFacade,
        GtmFacade $gtmFacade,
        CustomerUserFacade $customerUserFacade,
        Authenticator $authenticator,
        CustomerMailFacade $customerMailFacade,
        CustomerUserUpdateDataFactory $customerUserUpdateDataFactory,
        GoPayTransactionFacade $goPayTransactionFacade,
        OrderDataFactory $orderDataFactory,
        OrderItemFactory $orderItemFactory,
        OrderItemDataFactory $orderItemDataFactory,
        FreeTransportAndPaymentFacade $freeTransportAndPaymentFacade,
        CofidisFacade $cofidisFacade,
        HeurekaReviewFacade $heurekaReviewFacade,
        StoreFacade $storeFacade
    ) {
        $this->orderFacade = $orderFacade;
        $this->cartFacade = $cartFacade;
        $this->orderPreviewFactory = $orderPreviewFactory;
        $this->transportPriceCalculation = $transportPriceCalculation;
        $this->paymentPriceCalculation = $paymentPriceCalculation;
        $this->domain = $domain;
        $this->transportFacade = $transportFacade;
        $this->paymentFacade = $paymentFacade;
        $this->currencyFacade = $currencyFacade;
        $this->orderDataMapper = $orderDataMapper;
        $this->domainAwareOrderFlowFactory = $domainAwareOrderFlowFactory;
        $this->session = $session;
        $this->transportAndPaymentWatcher = $transportAndPaymentWatcher;
        $this->orderMailFacade = $orderMailFacade;
        $this->legalConditionsFacade = $legalConditionsFacade;
        $this->newsletterFacade = $newsletterFacade;
        $this->goPayBankSwiftFacade = $goPayBankSwiftFacade;
        $this->goPayFacadeOnCurrentDomain = $goPayFacadeOnCurrentDomain;
        $this->payPalFacade = $payPalFacade;
        $this->countryFacade = $countryFacade;
        $this->blogArticleFacade = $blogArticleFacade;
        $this->gtmFacade = $gtmFacade;
        $this->customerUserFacade = $customerUserFacade;
        $this->authenticator = $authenticator;
        $this->customerMailFacade = $customerMailFacade;
        $this->customerUserUpdateDataFactory = $customerUserUpdateDataFactory;
        $this->goPayTransactionFacade = $goPayTransactionFacade;
        $this->orderDataFactory = $orderDataFactory;
        $this->orderItemFactory = $orderItemFactory;
        $this->orderItemDataFactory = $orderItemDataFactory;
        $this->freeTransportAndPaymentFacade = $freeTransportAndPaymentFacade;
        $this->cofidisFacade = $cofidisFacade;
        $this->heurekaReviewFacade = $heurekaReviewFacade;
        $this->storeFacade = $storeFacade;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $cart = $this->cartFacade->findCartOfCurrentCustomerUser();
        $cart = $this->cartFacade->checkCartModificationsAndDeleteCartIfEmpty($cart);
        if ($cart === null) {
            return $this->redirectToRoute('front_cart');
        }
        /** @var \App\Model\Customer\User\CustomerUser|null $customerUser */
        $customerUser = $this->getUser();

        $frontOrderFormData = new FrontOrderData();
        if ($customerUser instanceof CustomerUser) {
            $this->orderFacade->prefillFrontOrderData($frontOrderFormData, $customerUser);
            /** @var \App\Model\Country\Country $country */
            $country = $customerUser->getCustomer()->getBillingAddress()->getCountry();
            $frontOrderFormData->country = $country;
        } else {
            $frontOrderFormData->country = $this->countryFacade->getHackedCountry();
        }
        $domainId = $this->domain->getId();
        $frontOrderFormData->domainId = $domainId;
        $currency = $this->currencyFacade->getDomainDefaultCurrencyByDomainId($domainId);
        $frontOrderFormData->currency = $currency;
        $goPayBankSwifts = $this->goPayBankSwiftFacade->getAllByCurrencyId($currency->getId());

        $orderFlow = $this->domainAwareOrderFlowFactory->create();
        if ($orderFlow->isBackToCartTransition()) {
            return $this->redirectToRoute('front_cart');
        }

        if ($this->session->has(CustomerLoginHandler::LOGGED_FROM_ORDER_SESSION_KEY)) {
            $orderFlow->mergePreviouslySavedFormDataWithLoggedUserData($customerUser);
        }

        $orderFlow->bind($frontOrderFormData);
        $orderFlow->saveSentStepData();

        if ($this->session->has(CustomerLoginHandler::LOGGED_FROM_ORDER_SESSION_KEY)) {
            $this->session->remove(CustomerLoginHandler::LOGGED_FROM_ORDER_SESSION_KEY);
            $orderFlow->nextStep();
        }

        $payment = $frontOrderFormData->payment;
        /** @var \App\Model\Transport\Transport $transport */
        $transport = $frontOrderFormData->transport;

        if ($transport !== null && $transport->isChooseStore()) {
            $frontOrderFormData->store = $this->storeFacade->getByPohodaName(Store::POHODA_STOCK_STORE_NAME);
        }

        $form = $orderFlow->createForm();
        $isValid = $orderFlow->isValid($form);

        // FormData are filled during isValid() call
        $orderData = $this->orderDataMapper->getOrderDataFromFrontOrderData($frontOrderFormData);
        if ($transport !== null && $transport->isChooseStore()) {
            $orderData->store = $this->storeFacade->getByPohodaName(Store::POHODA_STOCK_STORE_NAME);
        }

        if ($transport !== null && $transport->isPickupPlace()) {
            if ($orderData->pickupPlace !== null) {
                if ($transport->getBalikobotShipper() !== $orderData->pickupPlace->getBalikobotShipper() ||
                    $transport->getBalikobotShipperService() !== $orderData->pickupPlace->getBalikobotShipperService()
                ) {
                    $orderData->transport = null;
                    $orderData->pickupPlace = null;
                    $transport = null;
                    $form->get('transport')->setData(null);
                }
            }
        }

        $orderPreview = $this->orderPreviewFactory->createForCurrentUser($transport, $payment, $frontOrderFormData->registration);
        $orderFlow->setOrderPrice($orderPreview->getProductsPrice()->getPriceWithVat());

        $payments = $this->paymentFacade->getVisibleOnCurrentDomain();
        $transports = $this->transportFacade->getVisibleOnCurrentDomain($payments);
        $this->checkTransportAndPaymentChanges($orderData, $orderPreview, $transports, $payments);

        if ($isValid) {
            $cart = $this->cartFacade->checkCartModificationsAndDeleteCartIfEmpty($cart);
            if ($cart === null) {
                return $this->redirectToRoute('front_cart');
            }
            if ($orderFlow->nextStep()) {
                $form = $orderFlow->createForm();
            } elseif (!$this->existAnyErrorOrInfoMessages()) {
                try {
                    $order = $this->orderFacade->createOrderFromFront($orderData, $frontOrderFormData->deliveryAddress);
                    $this->orderFacade->sendHeurekaOrderInfo($order, $frontOrderFormData->disallowHeurekaVerifiedByCustomers);
                } catch (OutOfStockException $ex) {
                    return $this->redirectToRoute('front_order_index');
                }

                if ($frontOrderFormData->newsletterSubscription) {
                    $this->newsletterFacade->addSubscribedEmail($frontOrderFormData->email, $this->domain->getId());
                }

                if ($frontOrderFormData->registration && !$this->isUserLoggedOrRegistered($frontOrderFormData->email)) {
                    $customerData = $this->customerUserUpdateDataFactory->createFromOrder($order, $frontOrderFormData->password, $this->domain->getId());
                    $customer = $this->customerUserFacade->create($customerData);
                    $this->authenticator->loginUser($customer, $request);
                    $this->orderFacade->setCustomerToOrder($order, $customer);
                }

                $this->setGoPayBankSwiftSession($frontOrderFormData->payment, $frontOrderFormData->goPayBankSwift);

                $orderFlow->reset();

                $this->session->set(self::SESSION_CREATED_ORDER, $order->getId());

                try {
                    $this->sendMail($order);
                } catch (Exception $e) {
                    $this->addErrorFlash(
                        t('Unable to send some e-mails, please contact us for order verification.')
                    );
                }

                return $this->redirectToRoute('front_order_sent');
            }
        }

        if ($form->isSubmitted() && !$form->isValid() && $form->getErrors()->count() === 0) {
            $form->addError(new FormError(t('Please check the correctness of all data filled.')));
        }

        $this->setGtmDataLayer($orderFlow, $orderPreview);

        $pickupPlace = $transport !== null && $transport->isPacketaType() && isset($frontOrderFormData->packetaId) ? new PacketaPickupPlaceData(
            $frontOrderFormData->packetaId,
            $frontOrderFormData->packetaName,
            $frontOrderFormData->packetaCity,
            $frontOrderFormData->packetaStreet,
            $frontOrderFormData->packetaZip,
            $frontOrderFormData->packetaCountry
        ) : $orderData->pickupPlace;

        return $this->render('Front/Content/Order/index.html.twig', [
            'form' => $form->createView(),
            'flow' => $orderFlow,
            'transport' => $transport,
            'payment' => $payment,
            'payments' => $payments,
            'transportsPrices' => $this->transportPriceCalculation->getCalculatedPricesIndexedByTransportId(
                $transports,
                $currency,
                $orderPreview->getProductsPrice(),
                $domainId
            ),
            'paymentsPrices' => $this->paymentPriceCalculation->getCalculatedPricesIndexedByPaymentId(
                $payments,
                $currency,
                $orderPreview->getProductsPrice(),
                $domainId
            ),
            'goPayBankSwifts' => $goPayBankSwifts,
            'goPayBankTransferIdentifier' => GoPayPaymentMethod::IDENTIFIER_BANK_TRANSFER,
            'pickupPlace' => $pickupPlace,
            'store' => $orderData->store,
            'paymentTransportRelations' => $this->getPaymentTransportRelations($payments),
            'bulkyTransportRequired' => $this->cartFacade->isBulkyTransportRequired(),
            'oversizedTransportRequired' => $this->cartFacade->isOversizedTransportRequired(),
            'containsForeignSupplierProducts' => $this->cartFacade->containsForeignSupplierProducts(),
            'orderPreview' => $orderPreview,
        ]);
    }

    /**
     * @param \App\Model\Payment\Payment[] $payments
     * @return string
     */
    private function getPaymentTransportRelations(array $payments): string
    {
        $relations = [];
        foreach ($payments as $payment) {
            foreach ($payment->getTransports() as $transport) {
                $relations[] = [
                    'paymentId' => $payment->getId(),
                    'transportId' => $transport->getId(),
                ];
            }
        }

        return json_encode($relations);
    }

    /**
     * @param \App\Form\Front\Order\OrderFlow $orderFlow
     * @param \App\Model\Order\Preview\OrderPreview $orderPreview
     */
    private function setGtmDataLayer(OrderFlow $orderFlow, OrderPreview $orderPreview): void
    {
        switch ($orderFlow->getCurrentStep()) {
            case 2:
                $this->gtmFacade->onOrderTransportAndPaymentPage($orderPreview);
                break;
            case 3:
                $this->gtmFacade->onOrderDeliveryPage($orderPreview);
                break;
        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function transportAndPaymentBoxAction(Request $request): Response
    {
        $cart = $this->cartFacade->findCartOfCurrentCustomerUser();
        if ($cart === null) {
            return new Response('');
        }

        $customerUser = $this->getUser();

        $frontOrderFormData = new FrontOrderData();
        if ($customerUser instanceof CustomerUser) {
            $this->orderFacade->prefillFrontOrderData($frontOrderFormData, $customerUser);
        }
        $domainId = $this->domain->getId();
        $frontOrderFormData->domainId = $domainId;
        $currency = $this->currencyFacade->getDomainDefaultCurrencyByDomainId($domainId);
        $frontOrderFormData->currency = $currency;

        $orderFlow = $this->domainAwareOrderFlowFactory->create();
        if ($orderFlow->isBackToCartTransition()) {
            return $this->redirectToRoute('front_cart');
        }

        $country = $this->countryFacade->getById($request->get('countryId'));
        $orderFlow->setTransportCountry($country);

        $orderFlow->bind($frontOrderFormData);
        $orderFlow->saveSentStepData();

        $form = $orderFlow->createForm();

        $payment = $frontOrderFormData->payment;
        /** @var \App\Model\Transport\Transport $transport */
        $transport = $frontOrderFormData->transport;

        $orderPreview = $this->orderPreviewFactory->createForCurrentUser($transport, $payment);

        $orderData = $this->orderDataMapper->getOrderDataFromFrontOrderData($frontOrderFormData);

        if ($transport !== null && $transport->isPickupPlace()) {
            if ($orderData->pickupPlace !== null) {
                if ($transport->getBalikobotShipper() !== $orderData->pickupPlace->getBalikobotShipper() ||
                    $transport->getBalikobotShipperService() !== $orderData->pickupPlace->getBalikobotShipperService()
                ) {
                    $orderData->transport = null;
                    $orderData->pickupPlace = null;
                    $transport = null;
                    $form->get('transport')->setData(null);
                }
            }
        }

        $payments = $this->paymentFacade->getVisibleOnCurrentDomain();
        $transports = $this->transportFacade->getVisibleOnCurrentDomain($payments);
        $this->checkTransportAndPaymentChanges($orderData, $orderPreview, $transports, $payments);

        return $this->render('Front/Content/Order/transportAndPaymentBox.html.twig', [
            'form' => $form->createView(),
            'transportsPrices' => $this->transportPriceCalculation->getCalculatedPricesIndexedByTransportId(
                $transports,
                $currency,
                $orderPreview->getProductsPrice(),
                $domainId
            ),
            'paymentsPrices' => $this->paymentPriceCalculation->getCalculatedPricesIndexedByPaymentId(
                $payments,
                $currency,
                $orderPreview->getProductsPrice(),
                $domainId
            ),
            'goPayBankTransferIdentifier' => GoPayPaymentMethod::IDENTIFIER_BANK_TRANSFER,
            'pickupPlace' => $orderData->pickupPlace,
            'store' => $orderData->store,
            'bulkyTransportRequired' => $this->cartFacade->isBulkyTransportRequired(),
            'oversizedTransportRequired' => $this->cartFacade->isOversizedTransportRequired(),
            'orderPreview' => $orderPreview,
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function previewAction(Request $request)
    {
        $transportId = $request->get('transportId');
        $paymentId = $request->get('paymentId');
        $orderStep = $request->get('orderStep');
        $orderPreview = $request->get('orderPreview');

        if ($transportId === null) {
            $transport = null;
        } else {
            $transport = $this->transportFacade->getById($transportId);
        }

        if ($paymentId === null) {
            $payment = null;
        } else {
            $payment = $this->paymentFacade->getById($paymentId);
        }

        if ($orderPreview === null) {
            $orderPreview = $this->orderPreviewFactory->createForCurrentUser($transport, $payment);
        }
        $renderSubmitButton = $request->isXmlHttpRequest() === false || $orderStep === '1';
        $domainId = $this->domain->getId();
        $productsPrice = $orderPreview->getProductsPrice();

        return $this->render('Front/Content/Order/preview.html.twig', [
            'orderPreview' => $orderPreview,
            'orderStep' => $orderStep,
            'formSubmit' => $request->get('formSubmit'),
            'renderSubmitButton' => $renderSubmitButton,
            'termsAndConditionsArticle' => $this->legalConditionsFacade->findTermsAndConditions($domainId),
            'privacyPolicyArticle' => $this->legalConditionsFacade->findPrivacyPolicy($domainId),
            'orderGiftProductPrice' => Money::zero(),
            'isFreeTransportAndPaymentActive' => $this->freeTransportAndPaymentFacade->isActive($domainId),
            'isPaymentAndTransportFree' => $this->freeTransportAndPaymentFacade->isFree($productsPrice->getPriceWithVat(), $domainId),
            'remainingPriceWithVat' => $this->freeTransportAndPaymentFacade->getRemainingPriceWithVat($productsPrice->getPriceWithVat(), $domainId),
        ]);
    }

    /**
     * @param \App\Model\Order\OrderData $orderData
     * @param \App\Model\Order\Preview\OrderPreview $orderPreview
     * @param \App\Model\Transport\Transport[] $transports
     * @param \App\Model\Payment\Payment[] $payments
     */
    private function checkTransportAndPaymentChanges(
        OrderData $orderData,
        OrderPreview $orderPreview,
        array $transports,
        array $payments
    ) {
        $transportAndPaymentCheckResult = $this->transportAndPaymentWatcher->checkTransportAndPayment(
            $orderData,
            $orderPreview,
            $transports,
            $payments
        );

        if ($transportAndPaymentCheckResult->isTransportPriceChanged()) {
            $this->addInfoFlashTwig(
                t('The price of shipping {{ transportName }} changed during ordering process. Check your order, please.'),
                [
                    'transportName' => $orderData->transport->getName(),
                ]
            );
        }
        if ($transportAndPaymentCheckResult->isPaymentPriceChanged()) {
            $this->addInfoFlashTwig(
                t('The price of payment {{ paymentName }} changed during ordering process. Check your order, please.'),
                [
                    'paymentName' => $orderData->payment->getName(),
                ]
            );
        }
    }

    public function saveOrderFormAction()
    {
        $flow = $this->domainAwareOrderFlowFactory->create();
        $flow->bind(new FrontOrderData());
        $form = $flow->createForm();
        $flow->saveCurrentStepData($form);

        return new Response();
    }

    public function sentAction()
    {
        $orderId = $this->session->get(self::SESSION_CREATED_ORDER, null);
        $this->session->remove(self::SESSION_CREATED_ORDER);

        if ($orderId === null) {
            return $this->redirectToRoute('front_cart');
        }

        $order = $this->orderFacade->getById($orderId);
        $goPayData = null;

        if ($order->getPayment()->isGoPay()) {
            $goPayBankSwift = $this->session->get(self::SESSION_GOPAY_CHOOSEN_SWIFT, null);

            try {
                $goPayData = $this->goPayFacadeOnCurrentDomain->sendPaymentToGoPay($order, $goPayBankSwift);
                $this->goPayTransactionFacade->createNewTransactionByOrder($order, (string)$goPayData['goPayId']);
            } catch (\App\Model\GoPay\Exception\GoPayException $e) {
                $this->addErrorFlash(t('Connection to GoPay gateway failed.'));
            }
        }

        $this->session->remove(self::SESSION_GOPAY_CHOOSEN_SWIFT);

        $payPalApprovalLink = null;

        if ($order->getPayment()->isPayPal() && $order->getPayPalId() === null) {
            try {
                $payPalPayment = $this->payPalFacade->sendPayment($order);
                $payPalApprovalLink = $payPalPayment->getApprovalLink();
            } catch (\PayPal\Exception\PayPalConnectionException $e) {
                $this->addErrorFlash(t('Connection to PayPal gateway failed.'));
            }
        }

        $cofidisPaymentLink = $this->getCofidisPaymentLink($order);

        $this->gtmFacade->onOrderSentPage($order);

        return $this->render('Front/Content/Order/sent.html.twig', [
            'pageContent' => $this->orderFacade->getOrderSentPageContent($orderId),
            'order' => $order,
            'goPayData' => $goPayData,
            'payPalApprovalLink' => $payPalApprovalLink,
            'cofidisPaymentLink' => $cofidisPaymentLink,
            'homepageBlogArticles' => $this->blogArticleFacade->getHomepageBlogArticlesByDomainId(
                $this->domain->getId(),
                $this->domain->getLocale(),
                self::HOMEPAGE_ARTICLES_LIMIT
            ),
            'heurekaReviews' => $this->heurekaReviewFacade->getLatestReviews($this->domain->getId()),
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int $orderId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function registerCustomerAction(Request $request, int $orderId): Response
    {
        $form = $this->createForm(NewPasswordFormType::class, null);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            $newPassword = $formData['newPassword'];
            $order = $this->orderFacade->getById($orderId);
            $customerUserUpdateData = $this->customerUserUpdateDataFactory->createFromOrder($order, $newPassword, $this->domain->getId());
            /** @var \App\Model\Customer\User\CustomerUser $newlyRegisteredUser */
            $newlyRegisteredUser = $this->customerUserFacade->registerCustomer($customerUserUpdateData);
            try {
                $this->customerMailFacade->sendRegistrationMail($newlyRegisteredUser);
            } catch (\Swift_SwiftException | MailException $exception) {
                $this->addErrorFlash(
                    t('Unable to send some e-mails, please contact us for registration verification.')
                );
            }

            $this->orderFacade->setCustomerToOrder($order, $newlyRegisteredUser);

            $this->authenticator->loginUser($newlyRegisteredUser, $request);
            $this->addSuccessFlash(t('You have been successfully registered.'));
            return $this->redirectToRoute('front_customer_orders');
        }

        return $this->redirectToRoute('front_cart');
    }

    /**
     * @param string $urlHash
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function paidAction(string $urlHash): Response
    {
        try {
            /** @var \App\Model\Order\Order $order */
            $order = $this->orderFacade->getByUrlHashAndDomain($urlHash, $this->domain->getId());
        } catch (\Shopsys\FrameworkBundle\Model\Order\Exception\OrderNotFoundException $e) {
            $this->addErrorFlash(t('Order not found.'));

            return $this->redirectToRoute('front_cart');
        }

        if ($order->getPayment()->isGoPay()) {
            $this->checkOrderGoPayStatus($order);

            if ($this->goPayFacadeOnCurrentDomain->isOrderGoPayUnpaid($order)) {
                return $this->redirectToRoute('front_order_not_paid', ['urlHash' => $urlHash]);
            } else {
                if ($order->getPayment()->activatesGiftCertificates()) {
                    $this->orderFacade->activateGiftCertificates($order);
                }
            }
        }

        if ($order->getPayment()->isPayPal()) {
            $this->payPalFacade->executePayment($order);

            if (!$this->payPalFacade->isOrderPaid($order)) {
                return $this->redirectToRoute('front_order_not_paid', ['urlHash' => $urlHash]);
            }
        }

        $registrationForm = null;

        if ($this->isUserLoggedOrRegistered($order->getEmail()) === false) {
            $registrationForm = $this->createForm(NewPasswordFormType::class, null, [
                'action' => $this->generateUrl('front_order_register_customer', ['orderId' => $order->getId()]),
            ]);
        }

        return $this->render('Front/Content/Order/sent.html.twig', [
            'pageContent' => $this->orderFacade->getOrderSentPageContent($order->getId()),
            'order' => $order,
            'registrationForm' => $registrationForm !== null ? $registrationForm->createView() : null,
            'homepageBlogArticles' => $this->blogArticleFacade->getHomepageBlogArticlesByDomainId(
                $this->domain->getId(),
                $this->domain->getLocale(),
                self::HOMEPAGE_ARTICLES_LIMIT
            ),
            'heurekaReviews' => $this->heurekaReviewFacade->getLatestReviews($this->domain->getId()),
        ]);
    }

    /**
     * @param string $urlHash
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function notPaidAction(string $urlHash): Response
    {
        try {
            $order = $this->orderFacade->getByUrlHashAndDomain($urlHash, $this->domain->getId());
        } catch (\Shopsys\FrameworkBundle\Model\Order\Exception\OrderNotFoundException $e) {
            $this->addErrorFlash(t('Order not found.'));

            return $this->redirectToRoute('front_cart');
        }

        if (!$order->getPayment()->isGoPay()) {
            return $this->redirectToRoute('front_cart');
        }

        if ($this->orderFacade->isUnpaidOrderPaymentChangeable($order)) {
            $payments = $this->paymentFacade->getVisibleOnCurrentDomainByTransport($order->getTransport(), $order->getTotalProductPriceWithVat());
            $goPayBankSwifts = $this->goPayBankSwiftFacade->getAllByCurrencyId($order->getCurrency()->getId());

            $form = $this->createForm(PaymentFormType::class, [], [
                'action' => $this->generateUrl('front_order_change_payment_method', ['urlHash' => $order->getUrlHash()]),
                'method' => 'POST',
                'payments' => $payments,
                'goPayBankSwifts' => $goPayBankSwifts,
            ]);

            return $this->render('Front/Content/Order/changePayment.html.twig', [
                'form' => $form->createView(),
                'goPayBankTransferIdentifier' => GoPayPaymentMethod::IDENTIFIER_BANK_TRANSFER,
                'payments' => $payments,
                'urlHash' => $urlHash,
                'unsuccessfulPayment' => $order->getPayment(),
                'order' => $order,
            ]);
        } else {
            return $this->render('Front/Content/Order/notPaid.html.twig', [
                'goPayBankTransferIdentifier' => GoPayPaymentMethod::IDENTIFIER_BANK_TRANSFER,
                'urlHash' => $urlHash,
                'order' => $order,
            ]);
        }
    }

    /**
     * @param \App\Model\Order\Order $order
     */
    private function checkOrderGoPayStatus(BaseOrder $order): void
    {
        try {
            $this->goPayTransactionFacade->updateOrderTransactions($order);
        } catch (GoPayNotConfiguredException | GoPayPaymentDownloadException $e) {
            $this->addErrorFlash(t('Connection to GoPay gateway failed.'));
        }
    }

    /**
     * @param string $urlHash
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function repeatGoPayPaymentAction(string $urlHash): Response
    {
        try {
            /** @var \App\Model\Order\Order $order */
            $order = $this->orderFacade->getByUrlHashAndDomain($urlHash, $this->domain->getId());
        } catch (\Shopsys\FrameworkBundle\Model\Order\Exception\OrderNotFoundException $e) {
            $this->addErrorFlash(t('Objedn??vka nebyla nalezena.'));

            return $this->redirectToRoute('front_homepage');
        }

        $goPayData = null;

        if ($order->getPayment()->isGoPay()) {
            if ($order->isGopayPaid() !== false) {
                $this->addErrorFlash(t('Objedn??vka je ji?? zaplacen??.'));
                return $this->redirectToRoute('front_homepage');
            }
        } else {
            $this->addErrorFlash(t('Objedn??vka nem?? nastaven zp??sob platby prost??ednictv??m GoPay.'));
            return $this->redirectToRoute('front_homepage');
        }

        $goPayBankSwift = $this->session->get(self::SESSION_GOPAY_CHOOSEN_SWIFT, null);

        try {
            $goPayData = $this->goPayFacadeOnCurrentDomain->sendPaymentToGoPay($order, $goPayBankSwift);

            $this->goPayTransactionFacade->createNewTransactionByOrder($order, (string)$goPayData['goPayId']);
        } catch (\App\Model\GoPay\Exception\GoPayException $e) {
            $this->addErrorFlash(t('Connection to GoPay gateway failed.'));
        }

        return $this->render('Front/Content/Order/repeatGoPayPayment.html.twig', [
            'order' => $order,
            'goPayData' => $goPayData,
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $urlHash
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function changePaymentAction(Request $request, string $urlHash): RedirectResponse
    {
        try {
            $order = $this->orderFacade->getByUrlHashAndDomain($urlHash, $this->domain->getId());
        } catch (OrderNotFoundException $e) {
            $this->addErrorFlash(t('Objedn??vka nebyla nalezena.'));
            return $this->redirectToRoute('front_homepage');
        }

        $payments = $this->paymentFacade->getVisibleOnCurrentDomainByTransport($order->getTransport());

        $goPayBankSwifts = $this->goPayBankSwiftFacade->getAllByCurrencyId($order->getCurrency()->getId());

        $form = $this->createForm(PaymentFormType::class, [], [
            'payments' => $payments,
            'goPayBankSwifts' => $goPayBankSwifts,
        ]);

        $form->handleRequest($request);

        /** @var \App\Model\Payment\Payment $chosenPayment */
        $chosenPayment = $form['payment']->getData();
        $chosenGoPayPaymentSwift = $form['goPayBankSwift']->getData();
        $this->setGoPayBankSwiftSession($chosenPayment, $chosenGoPayPaymentSwift);

        $price = $this->paymentPriceCalculation->calculateIndependentPrice($chosenPayment, $order->getCurrency(), $this->domain->getId());
        $orderPayment = $this->orderItemFactory->createPayment($order, $chosenPayment->getName(), $price, $chosenPayment->getPaymentDomain($this->domain->getId())->getVat()->getPercent(), 1, $chosenPayment);
        $orderPaymentData = $this->orderItemDataFactory->createFromOrderItem($orderPayment);
        $orderData = $this->orderDataFactory->createFromOrder($order);
        $orderData->goPayId = null;
        $orderData->orderPayment = $orderPaymentData;
        $order->removeItem($order->getOrderPayment());

        if ($order->getExportStatus() === Order::EXPORT_SUCCESS) {
            $orderData->exportStatus = Order::EXPORT_NEEDS_UPDATE;
        }

        $this->orderFacade->edit($order->getId(), $orderData);

        $this->session->set(self::SESSION_CREATED_ORDER, $order->getId());

        $this->addInfoFlash(t('Zp??sob platby byl ??sp????n?? zm??n??n'));

        if ($order->getPayment()->isCofidis()) {
            $cofidisPaymentLink = $this->getCofidisPaymentLink($order);

            return $this->redirectToRoute('front_order_sent', [
                'pageContent' => $this->orderFacade->getOrderSentPageContent($order->getId()),
                'order' => $order,
                'goPayData' => null,
                'payPalApprovalLink' => null,
                'cofidisPaymentLink' => $cofidisPaymentLink,
                'homepageBlogArticles' => $this->blogArticleFacade->getHomepageBlogArticlesByDomainId(
                    $this->domain->getId(),
                    $this->domain->getLocale(),
                    self::HOMEPAGE_ARTICLES_LIMIT
                ),
            ]);
        }

        if ($this->getUser() instanceof CustomerUser) {
            return $this->redirectToRoute('front_customer_order_detail_registered', [
                'orderNumber' => $order->getNumber(),
            ]);
        }

        return $this->redirectToRoute('front_customer_order_detail_unregistered', [
            'urlHash' => $order->getUrlHash(),
        ]);
    }

    /**
     * @param \App\Model\Payment\Payment $payment
     * @param \App\Model\GoPay\BankSwift\GoPayBankSwift|null $goPayBankSwift
     */
    private function setGoPayBankSwiftSession(Payment $payment, ?GoPayBankSwift $goPayBankSwift): void
    {
        if ($payment->isGoPay()) {
            if ($goPayBankSwift !== null) {
                $goPayBankSwiftCode = $goPayBankSwift->getSwift();
            } else {
                $goPayBankSwiftCode = null;
            }

            $this->session->set(self::SESSION_GOPAY_CHOOSEN_SWIFT, $goPayBankSwiftCode);
        }
    }

    public function termsAndConditionsAction()
    {
        return $this->getTermsAndConditionsResponse();
    }

    public function termsAndConditionsDownloadAction()
    {
        $response = $this->getTermsAndConditionsResponse();

        return new DownloadFileResponse(
            $this->legalConditionsFacade->getTermsAndConditionsDownloadFilename(),
            $response->getContent(),
            'text/html'
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function getTermsAndConditionsResponse()
    {
        return $this->render('Front/Content/Order/legalConditions.html.twig', [
            'termsAndConditionsArticle' => $this->legalConditionsFacade->findTermsAndConditions($this->domain->getId()),
        ]);
    }

    /**
     * @param \App\Model\Order\Order $order
     */
    private function sendMail($order)
    {
        $mailTemplate = $this->orderMailFacade->getMailTemplateByStatusAndDomainId($order->getStatus(), $order->getDomainId());
        if ($mailTemplate->isSendMail()) {
            $this->orderMailFacade->sendEmail($order);
        }
    }

    /**
     * @param string $email
     * @return bool
     */
    private function isUserLoggedOrRegistered(string $email): bool
    {
        return $this->isGranted(Roles::ROLE_LOGGED_CUSTOMER) ||
            $this->customerUserFacade->findCustomerUserByEmailAndDomain($email, $this->domain->getId()) !== null;
    }

    /**
     * @param \App\Model\Order\Order $order
     * @return string|null
     */
    private function getCofidisPaymentLink(BaseOrder $order): ?string
    {
        $cofidisPaymentLink = null;

        try {
            $cofidisPaymentLink = $this->cofidisFacade->getCofidisPaymentLink($order);
        } catch (CofidisException $e) {
            $this->addErrorFlash(t('P??ipojen?? k br??n?? Cofidis selhalo.'));
        }

        return $cofidisPaymentLink;
    }
}
