<?php

declare(strict_types=1);

namespace App\Model\Order;

use App\Component\Domain\DomainHelper;
use App\Component\Mall\MallImportOrderClient;
use App\Component\SmsManager\SmsManagerFactory;
use App\Component\SmsManager\SmsMessageFactory;
use App\Model\Cart\Item\CartItem;
use App\Model\GoPay\GoPayTransaction;
use App\Model\Gtm\GtmHelper;
use App\Model\Order\Discount\CurrentOrderDiscountLevelFacade;
use App\Model\Order\GiftCertificate\OrderGiftCertificateFacade;
use App\Model\Order\Item\OrderItemFactory;
use App\Model\Order\Item\QuantifiedProduct;
use App\Model\Order\Mall\Exception\StatusChangException;
use App\Model\Order\Preview\OrderPreview;
use App\Model\Order\PromoCode\PromoCode;
use App\Model\Order\PromoCode\PromoCodeFacade;
use App\Model\Order\Status\OrderStatus;
use App\Model\Product\Gift\ProductGiftPriceCalculation;
use App\Model\Product\Product;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GoPay\Definition\Response\PaymentStatus;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Component\Setting\Setting;
use Shopsys\FrameworkBundle\Model\Administrator\Security\AdministratorFrontSecurityFacade;
use Shopsys\FrameworkBundle\Model\Cart\CartFacade;
use Shopsys\FrameworkBundle\Model\Customer\DeliveryAddress;
use Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser as BaseCustomerUser;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserFacade;
use Shopsys\FrameworkBundle\Model\Heureka\HeurekaFacade;
use Shopsys\FrameworkBundle\Model\Localization\Localization;
use Shopsys\FrameworkBundle\Model\Order\Exception\OrderNotFoundException;
use Shopsys\FrameworkBundle\Model\Order\FrontOrderDataMapper;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItem;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItemFactoryInterface;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItemPriceCalculation;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderProductFacade;
use Shopsys\FrameworkBundle\Model\Order\Mail\OrderMailFacade;
use Shopsys\FrameworkBundle\Model\Order\Order as BaseOrder;
use Shopsys\FrameworkBundle\Model\Order\OrderData as BaseOrderData;
use Shopsys\FrameworkBundle\Model\Order\OrderFacade as BaseOrderFacade;
use Shopsys\FrameworkBundle\Model\Order\OrderFactoryInterface;
use Shopsys\FrameworkBundle\Model\Order\OrderHashGeneratorRepository;
use Shopsys\FrameworkBundle\Model\Order\OrderNumberSequenceRepository;
use Shopsys\FrameworkBundle\Model\Order\OrderPriceCalculation;
use Shopsys\FrameworkBundle\Model\Order\OrderRepository;
use Shopsys\FrameworkBundle\Model\Order\OrderUrlGenerator;
use Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreview as BaseOrderPreview;
use Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreviewFactory;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\CurrentPromoCodeFacade;
use Shopsys\FrameworkBundle\Model\Order\Status\OrderStatusRepository;
use Shopsys\FrameworkBundle\Model\Payment\PaymentPriceCalculation;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Pricing\Vat\VatFacade;
use Shopsys\FrameworkBundle\Model\Transport\TransportPriceCalculation;
use Shopsys\FrameworkBundle\Twig\NumberFormatterExtension;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @property \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
 * @property \App\Component\Setting\Setting $setting
 * @property \App\Model\Cart\CartFacade $cartFacade
 * @property \App\Model\Customer\User\CustomerUserFacade $customerUserFacade
 * @property \App\Model\Order\Preview\OrderPreviewFactory $orderPreviewFactory
 * @property \App\Model\Order\FrontOrderDataMapper $frontOrderDataMapper
 * @property \App\Twig\NumberFormatterExtension $numberFormatterExtension
 * @property \App\Model\Payment\PaymentPriceCalculation $paymentPriceCalculation
 * @property \App\Model\Order\Item\OrderItemFactory $orderItemFactory
 * @method prefillFrontOrderData(\App\Model\Order\FrontOrderData $orderData, \App\Model\Customer\User\CustomerUser $customerUser)
 * @method \App\Model\Order\Order[] getCustomerUserOrderList(\App\Model\Customer\User\CustomerUser $customerUser)
 * @method \App\Model\Order\Order[] getOrderListForEmailByDomainId(string $email, int $domainId)
 * @method \App\Model\Order\Order getById(int $orderId)
 * @method \App\Model\Order\Order getByUrlHashAndDomain(string $urlHash, int $domainId)
 * @method \App\Model\Order\Order getByOrderNumberAndUser(string $orderNumber, \App\Model\Customer\User\CustomerUser $customerUser)
 * @method setOrderDataAdministrator(\App\Model\Order\OrderData $orderData)
 * @method calculateOrderItemDataPrices(\App\Model\Order\Item\OrderItemData $orderItemData, int $domainId)
 * @method fillOrderPayment(\App\Model\Order\Order $order, \App\Model\Order\Preview\OrderPreview $orderPreview, string $locale)
 * @method fillOrderTransport(\App\Model\Order\Order $order, \App\Model\Order\Preview\OrderPreview $orderPreview, string $locale)
 * @method fillOrderRounding(\App\Model\Order\Order $order, \App\Model\Order\Preview\OrderPreview $orderPreview, string $locale)
 * @method refreshOrderItemsWithoutTransportAndPayment(\App\Model\Order\Order $order, \App\Model\Order\OrderData $orderData)
 * @property \App\Model\Order\Status\OrderStatusRepository $orderStatusRepository
 * @property \App\Model\Order\Item\OrderProductFacade $orderProductFacade
 * @method updateTransportAndPaymentNamesInOrderData(\App\Model\Order\OrderData $orderData, \App\Model\Order\Order $order)
 * @property \App\Model\Transport\TransportPriceCalculation $transportPriceCalculation
 * @property \App\Model\Order\OrderNumberSequenceRepository $orderNumberSequenceRepository
 */
class OrderFacade extends BaseOrderFacade
{
    /**
     * @var \App\Model\Order\OrderRepository
     */
    protected $orderRepository;

    /**
     * @var \App\Model\Order\PromoCode\CurrentPromoCodeFacade
     */
    protected $currentPromoCodeFacade;

    /**
     * @var \App\Model\Product\Gift\ProductGiftPriceCalculation
     */
    private $productGiftPriceCalculation;

    /**
     * @var \App\Component\Mall\MallImportOrderClient
     */
    private $mallImportOrderClient;

    /**
     * @var \App\Model\Pricing\Vat\VatFacade
     */
    private $vatFacade;

    /**
     * @var \App\Model\Gtm\GtmHelper
     */
    private $gtmHelper;

    /**
     * @var \App\Component\SmsManager\SmsManagerFactory
     */
    private $smsManagerFactory;

    /**
     * @var \App\Component\SmsManager\SmsMessageFactory
     */
    private $smsMessageFactory;

    /**
     * @var \App\Model\Order\PromoCode\PromoCodeFacade
     */
    private $promoCodeFacade;

    /**
     * @var \App\Model\Order\GiftCertificate\OrderGiftCertificateFacade
     */
    private $orderGiftCertificateFacade;

    /**
     * @var \App\Model\Order\Discount\CurrentOrderDiscountLevelFacade
     */
    private $currentOrderDiscountLevelFacade;

    /**
     * @var \Symfony\Component\HttpFoundation\Session\Session
     */
    private SessionInterface $session;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Order\OrderNumberSequenceRepository $orderNumberSequenceRepository
     * @param \App\Model\Order\OrderRepository $orderRepository
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderUrlGenerator $orderUrlGenerator
     * @param \App\Model\Order\Status\OrderStatusRepository $orderStatusRepository
     * @param \Shopsys\FrameworkBundle\Model\Order\Mail\OrderMailFacade $orderMailFacade
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderHashGeneratorRepository $orderHashGeneratorRepository
     * @param \App\Component\Setting\Setting $setting
     * @param \Shopsys\FrameworkBundle\Model\Localization\Localization $localization
     * @param \Shopsys\FrameworkBundle\Model\Administrator\Security\AdministratorFrontSecurityFacade $administratorFrontSecurityFacade
     * @param \App\Model\Order\PromoCode\CurrentPromoCodeFacade $currentPromoCodeFacade
     * @param \App\Model\Cart\CartFacade $cartFacade
     * @param \App\Model\Customer\User\CustomerUserFacade $customerUserFacade
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser $currentCustomerUser
     * @param \App\Model\Order\Preview\OrderPreviewFactory $orderPreviewFactory
     * @param \App\Model\Order\Item\OrderProductFacade $orderProductFacade
     * @param \Shopsys\FrameworkBundle\Model\Heureka\HeurekaFacade $heurekaFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderFactoryInterface $orderFactory
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderPriceCalculation $orderPriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\OrderItemPriceCalculation $orderItemPriceCalculation
     * @param \App\Model\Order\FrontOrderDataMapper $frontOrderDataMapper
     * @param \App\Twig\NumberFormatterExtension $numberFormatterExtension
     * @param \App\Model\Payment\PaymentPriceCalculation $paymentPriceCalculation
     * @param \App\Model\Transport\TransportPriceCalculation $transportPriceCalculation
     * @param \App\Model\Order\Item\OrderItemFactory $orderItemFactory
     * @param \App\Model\Product\Gift\ProductGiftPriceCalculation $productGiftPriceCalculation
     * @param \App\Model\Pricing\Vat\VatFacade $vatFacade
     * @param \App\Component\Mall\MallImportOrderClient $mallImportOrderClient
     * @param \App\Model\Gtm\GtmHelper $gtmHelper
     * @param \App\Component\SmsManager\SmsManagerFactory $smsManagerFactory
     * @param \App\Component\SmsManager\SmsMessageFactory $smsMessageFactory
     * @param \App\Model\Order\PromoCode\PromoCodeFacade $promoCodeFacade
     * @param \App\Model\Order\GiftCertificate\OrderGiftCertificateFacade $orderGiftCertificateFacade
     * @param \App\Model\Order\Discount\CurrentOrderDiscountLevelFacade $currentOrderDiscountLevelFacade
     * @param \Symfony\Component\HttpFoundation\Session\Session $session
     */
    public function __construct(
        EntityManagerInterface $em,
        OrderNumberSequenceRepository $orderNumberSequenceRepository,
        OrderRepository $orderRepository,
        OrderUrlGenerator $orderUrlGenerator,
        OrderStatusRepository $orderStatusRepository,
        OrderMailFacade $orderMailFacade,
        OrderHashGeneratorRepository $orderHashGeneratorRepository,
        Setting $setting,
        Localization $localization,
        AdministratorFrontSecurityFacade $administratorFrontSecurityFacade,
        CurrentPromoCodeFacade $currentPromoCodeFacade,
        CartFacade $cartFacade,
        CustomerUserFacade $customerUserFacade,
        CurrentCustomerUser $currentCustomerUser,
        OrderPreviewFactory $orderPreviewFactory,
        OrderProductFacade $orderProductFacade,
        HeurekaFacade $heurekaFacade,
        Domain $domain,
        OrderFactoryInterface $orderFactory,
        OrderPriceCalculation $orderPriceCalculation,
        OrderItemPriceCalculation $orderItemPriceCalculation,
        FrontOrderDataMapper $frontOrderDataMapper,
        NumberFormatterExtension $numberFormatterExtension,
        PaymentPriceCalculation $paymentPriceCalculation,
        TransportPriceCalculation $transportPriceCalculation,
        OrderItemFactoryInterface $orderItemFactory,
        ProductGiftPriceCalculation $productGiftPriceCalculation,
        VatFacade $vatFacade,
        MallImportOrderClient $mallImportOrderClient,
        GtmHelper $gtmHelper,
        SmsManagerFactory $smsManagerFactory,
        SmsMessageFactory $smsMessageFactory,
        PromoCodeFacade $promoCodeFacade,
        OrderGiftCertificateFacade $orderGiftCertificateFacade,
        CurrentOrderDiscountLevelFacade $currentOrderDiscountLevelFacade,
        SessionInterface $session
    ) {
        parent::__construct(
            $em,
            $orderNumberSequenceRepository,
            $orderRepository,
            $orderUrlGenerator,
            $orderStatusRepository,
            $orderMailFacade,
            $orderHashGeneratorRepository,
            $setting,
            $localization,
            $administratorFrontSecurityFacade,
            $currentPromoCodeFacade,
            $cartFacade,
            $customerUserFacade,
            $currentCustomerUser,
            $orderPreviewFactory,
            $orderProductFacade,
            $heurekaFacade,
            $domain,
            $orderFactory,
            $orderPriceCalculation,
            $orderItemPriceCalculation,
            $frontOrderDataMapper,
            $numberFormatterExtension,
            $paymentPriceCalculation,
            $transportPriceCalculation,
            $orderItemFactory
        );

        $this->productGiftPriceCalculation = $productGiftPriceCalculation;
        $this->vatFacade = $vatFacade;
        $this->mallImportOrderClient = $mallImportOrderClient;
        $this->gtmHelper = $gtmHelper;
        $this->smsManagerFactory = $smsManagerFactory;
        $this->smsMessageFactory = $smsMessageFactory;
        $this->promoCodeFacade = $promoCodeFacade;
        $this->orderGiftCertificateFacade = $orderGiftCertificateFacade;
        $this->currentOrderDiscountLevelFacade = $currentOrderDiscountLevelFacade;
        $this->session = $session;
    }

    /**
     * @param int $orderId
     * @return string
     */
    public function getOrderSentPageContent($orderId): string
    {
        /** @var \App\Model\Order\Order $order */
        $order = $this->getById($orderId);
        $orderSentPageContent = parent::getOrderSentPageContent($orderId);

        if ($order->isGopayPaid()) {
            $orderSentPageContent = str_replace(
                $order->getPayment()->getInstructions(),
                t('You have successfully paid order via GoPay.'),
                $orderSentPageContent
            );
        }

        return $orderSentPageContent;
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param string $payPalStatus
     */
    public function setPayPalStatus(BaseOrder $order, string $payPalStatus): void
    {
        $order->setPayPalStatus($payPalStatus);
        $this->em->flush($order);
    }

    /**
     * @param \DateTime $fromDate
     * @return \App\Model\Order\Order[]
     */
    public function getAllUnpaidGoPayOrders(\DateTime $fromDate): array
    {
        return $this->orderRepository->getAllUnpaidGoPayOrders($fromDate);
    }

    /**
     * @param \DateTime $fromDate
     * @return \App\Model\Order\Order[]
     */
    public function getAllUnpaidPayPalOrders(\DateTime $fromDate): array
    {
        return $this->orderRepository->getAllUnpaidPayPalOrders($fromDate);
    }

    /**
     * @param \App\Model\Order\OrderData $orderData
     * @param \App\Model\Customer\DeliveryAddress|null $deliveryAddress
     * @return \App\Model\Order\Order
     */
    public function createOrderFromFront(BaseOrderData $orderData, ?DeliveryAddress $deliveryAddress): BaseOrder
    {
        /** @var \App\Model\Order\Status\OrderStatus $defaultOrderStatus */
        $defaultOrderStatus = $this->orderStatusRepository->getDefault();
        $orderData->status = $defaultOrderStatus;
        $validEnteredPromoCodes = $this->currentPromoCodeFacade->getValidEnteredPromoCodes();
        $orderPreview = $this->orderPreviewFactory->createForCurrentUser($orderData->transport, $orderData->payment, $orderData->registration);
        /** @var \App\Model\Customer\User\CustomerUser $customerUser */
        $customerUser = $this->currentCustomerUser->findCurrentCustomerUser();
        $this->gtmHelper->amendGtmCouponToOrderData($orderData, $validEnteredPromoCodes);

        foreach ($validEnteredPromoCodes as $validEnteredPromoCode) {
            $orderData->promoCodesCodes[] = $validEnteredPromoCode->getCode();
            $this->currentPromoCodeFacade->usePromoCode($validEnteredPromoCode);
            $this->currentPromoCodeFacade->removeEnteredPromoCodeByCode($validEnteredPromoCode->getCode());
        }

        $this->updateOrderDataWithDeliveryAddress($orderData, $deliveryAddress);
        $order = $this->createOrder($orderData, $orderPreview, $customerUser);
        $this->orderProductFacade->subtractOrderProductsFromStock($order->getProductItems());
        $this->orderProductFacade->subtractOrderProductsFromStock($order->getGiftItems());
        $this->processGiftCertificates($order);

        $this->cartFacade->deleteCartOfCurrentCustomerUser();
        $this->currentOrderDiscountLevelFacade->unsetActiveOrderLevelDiscount();
        $this->unsetOrderPreviewInfoFromSession();

        if ($customerUser !== null) {
            $order->setCustomerTransferId($customerUser->getTransferId());
            $this->customerUserFacade->amendCustomerUserDataFromOrder($customerUser, $order, $deliveryAddress);
            $this->em->flush($order);
        }

        return $order;
    }

    private function unsetOrderPreviewInfoFromSession(): void
    {
        $this->session->remove(OrderPreview::ITEMS_COUNT_SESSION_KEY);
        $this->session->remove(OrderPreview::TOTAL_PRICE_SESSION_KEY);
    }

    /**
     * @param \App\Model\Order\OrderData $orderData
     * @param \App\Model\Customer\DeliveryAddress|null $deliveryAddress
     */
    protected function updateOrderDataWithDeliveryAddress(BaseOrderData $orderData, ?DeliveryAddress $deliveryAddress)
    {
        if ($deliveryAddress !== null) {
            $orderData->deliveryCompanyName = $deliveryAddress->getCompanyName();
            $orderData->deliveryStreet = $deliveryAddress->getStreet();
            $orderData->deliveryPostcode = $deliveryAddress->getPostcode();
            $orderData->deliveryCity = $deliveryAddress->getCity();
            $orderData->deliveryCountry = $deliveryAddress->getCountry();
        }
    }

    /**
     * @param \DateTime $startTime
     * @param \DateTime $endTime
     * @return int[]
     */
    public function getCustomerIdsFromOrdersByDatePeriod(DateTime $startTime, DateTime $endTime): array
    {
        return $this->orderRepository->getCustomerIdsFromOrdersByDatePeriod($startTime, $endTime);
    }

    /**
     * @param int[] $customerIds
     * @param \DateTime $endTime
     * @return \Shopsys\FrameworkBundle\Component\Money\Money[]
     */
    public function getOrdersValueIndexedByCustomerIdOlderThanDate(array $customerIds, DateTime $endTime): array
    {
        return $this->orderRepository->getOrdersValueIndexedByCustomerIdOlderThanDate($customerIds, $endTime);
    }

    /**
     * @param int $orderId
     * @param int|null $pohodaId
     */
    public function markOrderAsExported(int $orderId, ?int $pohodaId): void
    {
        /** @var \App\Model\Order\Order $order */
        $order = $this->getById($orderId);
        $order->markAsExported($pohodaId);

        $this->em->flush($order);
    }

    /**
     * @param int $orderId
     */
    public function markOrderAsFailedExported(int $orderId): void
    {
        /** @var \App\Model\Order\Order $order */
        $order = $this->getById($orderId);
        $order->markAsFailedExported();

        $this->em->flush($order);
    }

    /**
     * @param int $limit
     * @return \App\Model\Order\Order[]
     */
    public function getReadyOrdersForExportBatch(int $limit): array
    {
        return $this->orderRepository->getReadyOrdersForExportBatch($limit);
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param \App\Model\Order\Preview\OrderPreview $orderPreview
     */
    protected function fillOrderItems(BaseOrder $order, BaseOrderPreview $orderPreview): void
    {
        $locale = $this->domain->getDomainConfigById($order->getDomainId())->getLocale();

        $this->fillOrderProducts($order, $orderPreview, $locale);
        $this->fillOrderRounding($order, $orderPreview, $locale);

        $this->fillOrderGift($order, $orderPreview);

        $promoCodes = $orderPreview->getPromoCodesIndexedById();
        foreach ($promoCodes as $promoCode) {
            if ($promoCode->isTypeGiftCertificate()) {
                $this->setGiftCertificate(
                    $orderPreview,
                    $order,
                    $promoCode
                );
            }
        }

        $this->fillOrderPayment($order, $orderPreview, $locale);
        $this->fillOrderTransport($order, $orderPreview, $locale);
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param \App\Model\Order\Preview\OrderPreview $orderPreview
     */
    private function fillOrderGift(Order $order, OrderPreview $orderPreview)
    {
        $orderGiftProduct = $orderPreview->getOrderGiftProduct();

        if ($orderGiftProduct !== null) {
            $giftPrice = new Price(Money::zero(), Money::zero());

            $this->orderItemFactory->createGift(
                $order,
                $orderGiftProduct->getName($this->domain->getLocale()),
                $giftPrice,
                $orderGiftProduct->getVatForDomain($order->getDomainId())->getPercent(),
                1,
                $orderGiftProduct->getUnit()->getName($this->domain->getLocale()),
                $orderGiftProduct->getCatnum(),
                $orderGiftProduct,
                $giftPrice
            );
        }
    }

    /**
     * @param string $number
     * @return \App\Model\Order\Order|null
     */
    public function findByNumber(string $number): ?Order
    {
        return $this->orderRepository->findByNumber($number);
    }

    /**
     * @param int $limit
     * @return \App\Model\Order\Order[]
     */
    public function getBatchToCheckOrderStatus(int $limit): array
    {
        return $this->orderRepository->getBatchToCheckOrderStatus($limit);
    }

    /**
     * @param string $number
     */
    public function updateStatusCheckedAtByNumber(string $number): void
    {
        $order = $this->orderRepository->getByNumber($number);

        $order->updateStatusCheckedAt();
        $this->em->flush($order);
    }

    /**
     * @param int $orderId
     * @param \App\Model\Order\OrderData $orderData
     * @param string|null $locale
     * @return \App\Model\Order\Order
     */
    public function edit($orderId, BaseOrderData $orderData, ?string $locale = null)
    {
        /** @var \App\Model\Order\Order $order */
        $order = $this->orderRepository->getById($orderId);
        $originalMallStatus = $order->getMallStatus();
        $originalOrderStatus = $order->getStatus();
        $orderData->orderPayment->name = $orderData->orderPayment->payment->getName($locale);
        $orderData->orderTransport->name = $orderData->orderTransport->transport->getName($locale);
        /** @var \App\Model\Order\Order $updatedOrder */
        $updatedOrder = parent::edit($orderId, $orderData);

        if ($originalMallStatus !== $updatedOrder->getMallStatus()) {
            try {
                $this->mallImportOrderClient->changeStatus((int)$updatedOrder->getMallOrderId(), $originalMallStatus, $updatedOrder->getMallStatus());
            } catch (Exception $ex) {
                throw new StatusChangException($ex);
            }
        }

        if ($originalOrderStatus !== $updatedOrder->getStatus()) {
            if ($updatedOrder->getStatus()->activatesGiftCertificates()) {
                $this->activateGiftCertificates($updatedOrder);
            }
        }

        return $updatedOrder;
    }

    /**
     * @param \App\Model\Order\Order $order
     */
    public function sendSms(BaseOrder $order): void
    {
        if ($order->getDomainId() !== DomainHelper::CZECH_DOMAIN) {
            return;
        }

        $smsMessage = $this->smsMessageFactory->getSmsMessageForOrder($order);
        if ($smsMessage !== null) {
            try {
                $this->smsManagerFactory->getManager()->send($smsMessage);
            } catch (Exception $ex) {
            }
        }
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param \App\Model\Customer\User\CustomerUser $customer
     */
    public function setCustomerToOrder(BaseOrder $order, BaseCustomerUser $customer): void
    {
        $order->setCustomer($customer);
        $this->em->flush($order);
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param \App\Model\Order\Preview\OrderPreview $orderPreview
     * @param string $locale
     */
    public function fillOrderProducts(
        BaseOrder $order,
        BaseOrderPreview $orderPreview,
        string $locale
    ): void {
        $quantifiedItemPrices = $orderPreview->getQuantifiedItemsPrices();
        $quantifiedItemDiscountsIndexedByPromoCodeId = $orderPreview->getQuantifiedItemsDiscountsIndexedByPromoCodeId();
        $orderDiscountLevelQuantifiedItemDiscountsByIndex = $orderPreview->getQuantifiedItemsDiscounts();

        foreach ($orderPreview->getQuantifiedProducts() as $index => $quantifiedProduct) {
            /** @var \App\Model\Product\Product $product */
            $product = $quantifiedProduct->getProduct();

            $quantifiedItemPrice = $quantifiedItemPrices[$index];
            /* @var $quantifiedItemPrice \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice */

            $orderItem = $this->orderItemFactory->createProduct(
                $order,
                $quantifiedProduct->getName($locale),
                $quantifiedItemPrice->getUnitPrice(),
                $product->getVatForDomain($order->getDomainId())->getPercent(),
                $quantifiedProduct->getQuantity(),
                $product->getUnit()->getName($locale),
                $product->getCatnum(),
                $product,
                $quantifiedProduct->isSaleItem()
            );

            foreach ($quantifiedItemDiscountsIndexedByPromoCodeId as $promoCodeId => $quantifiedItemDiscounts) {
                $quantifiedItemDiscount = $quantifiedItemDiscounts[$index];
                /* @var $quantifiedItemDiscount \Shopsys\FrameworkBundle\Model\Pricing\Price|null */
                if ($quantifiedItemDiscount !== null) {
                    $promoCode = $orderPreview->getPromoCodeById($promoCodeId);
                    $this->addOrderItemDiscount($orderItem, $quantifiedItemDiscount, $locale, (float)$orderPreview->getPromoCodeDiscountPercent(), $promoCode);
                }
            }

            $orderDiscountLevel = $orderPreview->getActiveOrderDiscountLevel();
            if ($orderDiscountLevel !== null && !empty($orderDiscountLevelQuantifiedItemDiscountsByIndex[$index])) {
                $this->addOrderDiscountLevelItem(
                    $orderItem,
                    $orderDiscountLevelQuantifiedItemDiscountsByIndex[$index],
                    $locale,
                    $orderDiscountLevel->getDiscountPercent()
                );
            }

            $giftForProduct = $orderPreview->getGiftForProduct($product);

            if ($giftForProduct !== null && $this->isAllowedToAddGiftToOrder($orderPreview, $quantifiedProduct)) {
                $this->fillOrderProductGift($order, $giftForProduct);
            }
        }
    }

    /**
     * @param \App\Model\Order\Preview\OrderPreview $orderPreview
     * @param \App\Model\Order\Item\QuantifiedProduct $currentQuantifiedProduct
     * @return bool
     */
    private function isAllowedToAddGiftToOrder(OrderPreview $orderPreview, QuantifiedProduct $currentQuantifiedProduct): bool
    {
        $productCountInOrder = 0;
        foreach ($orderPreview->getQuantifiedProducts() as $quantifiedProduct) {
            if ($quantifiedProduct->getProduct()->getId() === $currentQuantifiedProduct->getProduct()->getId()) {
                $productCountInOrder++;
            }
        }

        return $productCountInOrder === 1 || ($productCountInOrder > 1 && !$currentQuantifiedProduct->isSaleItem());
    }

    /**
     * @param \App\Model\Order\Item\OrderItem $orderItem
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $quantifiedItemDiscount
     * @param string $locale
     * @param float $discountPercent
     * @param \App\Model\Order\PromoCode\PromoCode|null $promoCode
     */
    protected function addOrderItemDiscount(
        OrderItem $orderItem,
        Price $quantifiedItemDiscount,
        string $locale,
        float $discountPercent,
        ?PromoCode $promoCode = null
    ): void {
        if ($promoCode->isUseNominalDiscount()) {
            $discountValue = $this->numberFormatterExtension->formatNumber('-' . $promoCode->getNominalDiscount()->getAmount()) . ' ' . $this->numberFormatterExtension->getCurrencySymbolByCurrencyIdAndLocale($orderItem->getOrder()->getDomainId(), $locale);
        } else {
            $discountValue = $this->numberFormatterExtension->formatPercent('-' . $promoCode->getPercent(), $locale);
        }

        $name = sprintf(
            '%s %s %s (SP) - %s',
            t('Promo code', [], 'messages', $locale),
            $promoCode->getCode(),
            $discountValue,
            $orderItem->getName(),
        );

        $this->orderItemFactory->createPromoCode(
            $name,
            $quantifiedItemDiscount->inverse(),
            $orderItem
        );
    }

    /**
     * @param \App\Model\Order\Item\OrderItem $orderItem
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $quantifiedItemDiscount
     * @param string $locale
     * @param int $discountPercent
     */
    private function addOrderDiscountLevelItem(
        OrderItem $orderItem,
        Price $quantifiedItemDiscount,
        string $locale,
        int $discountPercent
    ): void {
        $discountValue = $this->numberFormatterExtension->formatPercent('-' . $discountPercent, $locale);

        $name = sprintf(
            '%s %s (BP) - %s',
            t('Sleva', [], 'messages', $locale),
            $discountValue,
            $orderItem->getName()
        );

        $this->orderItemFactory->createOrderDiscountLevel(
            $name,
            $quantifiedItemDiscount->inverse(),
            $orderItem
        );
    }

    /**
     * @param \App\Model\Order\Preview\OrderPreview $orderPreview
     * @param \App\Model\Order\Order $order
     * @param \App\Model\Order\PromoCode\PromoCode $promoCode
     * @throws \Shopsys\FrameworkBundle\Component\Domain\Exception\NoDomainSelectedException
     */
    private function setGiftCertificate(
        OrderPreview $orderPreview,
        BaseOrder $order,
        PromoCode $promoCode
    ): void {
        $locale = $this->domain->getCurrentDomainConfig()->getLocale();
        $name = sprintf(
            '%s %s %s (DP)',
            t('Dárkový certifikát ', [], 'messages', $locale),
            $promoCode->getCode(),
            $this->numberFormatterExtension->formatNumber($promoCode->getCertificateValue()->getAmount()) . ' ' . $this->numberFormatterExtension->getCurrencySymbolByCurrencyIdAndLocale($order->getDomainId(), $locale)
        );

        $certificatePrice = new Price($promoCode->getCertificateValue(), $promoCode->getCertificateValue());
        if ($certificatePrice->getPriceWithVat()->isGreaterThan($orderPreview->getTotalPriceWithoutGiftCertificate()->getPriceWithVat())) {
            $certificatePrice = $orderPreview->getTotalPriceWithoutGiftCertificate();
        }

        $this->orderItemFactory->createGiftCertificate(
            $order,
            $name,
            $certificatePrice,
            $promoCode->getCertificateSku(),
            $this->vatFacade->getDefaultVatForDomain($order->getDomainId())->getPercent()
        );
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param \App\Model\Cart\Item\CartItem $giftInCart
     */
    private function fillOrderProductGift(BaseOrder $order, CartItem $giftInCart): void
    {
        $gift = $giftInCart->getProduct();

        if (!$this->orderItemFactory instanceof OrderItemFactory) {
            $message = 'Object "' . get_class($this->orderItemFactory) . '" has to be instance of \App\Model\Order\Item\OrderItemFactory.';
            throw new \Symfony\Component\Config\Definition\Exception\InvalidTypeException($message);
        }

        $giftPrice = new Price($this->productGiftPriceCalculation->getGiftPrice(), $this->productGiftPriceCalculation->getGiftPrice());
        $giftTotalPrice = new Price(
            $this->productGiftPriceCalculation->getGiftPrice()->multiply($giftInCart->getQuantity()),
            $this->productGiftPriceCalculation->getGiftPrice()->multiply($giftInCart->getQuantity())
        );

        $this->orderItemFactory->createGift(
            $order,
            $gift->getName($this->domain->getLocale()),
            $giftPrice,
            $gift->getVatForDomain($order->getDomainId())->getPercent(),
            $giftInCart->getQuantity(),
            $gift->getUnit()->getName($this->domain->getLocale()),
            $gift->getCatnum(),
            $gift,
            $giftTotalPrice
        );
    }

    /**
     * @param \App\Model\Order\Order $order
     * @return bool
     */
    public function isUnpaidOrderPaymentChangeable(Order $order): bool
    {
        return $order->getStatus()->getType() === OrderStatus::TYPE_NEW &&
            $order->getPayment()->isGoPay() &&
            count(array_filter($order->getGoPayTransactions(), function (GoPayTransaction $transaction) {
                return $transaction->getGoPayStatus() === PaymentStatus::PAID;
            })) === 0;
    }

    /**
     * @param \App\Model\Order\Order $order
     */
    private function processGiftCertificates(Order $order): void
    {
        foreach ($order->getItems() as $orderItem) {
            if ($orderItem->isTypeProduct() && $orderItem->getProduct() instanceof Product && $orderItem->getProduct()->isGiftCertificate()) {
                $giftCertificates = $this->promoCodeFacade->createRandomCertificates($orderItem->getPriceWithVat(), $orderItem->getQuantity(), $order->getDomainId());

                foreach ($giftCertificates as $giftCertificate) {
                    $orderGiftCertificate = $this->orderGiftCertificateFacade->create($order, $giftCertificate);
                    $order->addGiftCertificate($orderGiftCertificate);
                }
            }
        }
    }

    /**
     * @param \App\Model\Order\Order $order
     */
    public function activateGiftCertificates(Order $order): void
    {
        $this->orderGiftCertificateFacade->activate($order->getGiftCertificates());
    }

    /**
     * @return \App\Model\Order\Order[]
     */
    public function findAll(): array
    {
        return $this->orderRepository->findAll();
    }

    /**
     * @param string $email
     * @param int $domainId
     * @return \App\Model\Order\Order
     */
    public function getNewestByEmailAndDomain(string $email, int $domainId): Order
    {
        $order = $this->orderRepository->findNewestByEmailAndDomainId($email, $domainId);

        if ($order === null) {
            throw new OrderNotFoundException();
        }

        return $order;
    }

    /**
     * @param int $limit
     * @return \App\Model\Order\Order[]
     */
    public function getAllForTransfer(int $limit): array
    {
        return $this->orderRepository->getForTransfer($limit);
    }

    /**
     * @return \App\Model\Order\Order[]
     */
    public function getAllForExportToZbozi(): array
    {
        return $this->orderRepository->getAllForExportToZbozi();
    }

    /**
     * @param int[] $orderIds
     */
    public function markOrdersAsExportedToZbozi(array $orderIds): void
    {
        $this->orderRepository->markOrdersAsExportedToZbozi($orderIds);
    }

    /**
     * @param int $pohodaId
     * @return \App\Model\Order\Order|null
     */
    public function findByPohodaId(int $pohodaId): ?Order
    {
        return $this->orderRepository->findByPohodaId($pohodaId);
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param bool $disallowHeurekaVerifiedByCustomers
     */
    public function sendHeurekaOrderInfo(BaseOrder $order, $disallowHeurekaVerifiedByCustomers)
    {
        foreach ($order->getItems() as $item) {
            if ($item->isTypeProduct() && $item->getProduct() !== null && $item->getProduct()->isForeignSupplier()) {
                return;
            }
        }
        parent::sendHeurekaOrderInfo($order, $disallowHeurekaVerifiedByCustomers);
    }

    /**
     * @param int $legacyId
     * @return \App\Model\Order\Order|null
     */
    public function findByLegacyId(int $legacyId): ?Order
    {
        return $this->orderRepository->findByLegacyId($legacyId);
    }

    /**
     * @param \DateTime $fromDate
     * @return \App\Model\Order\Order[]
     */
    public function getOrdersWithLegacyIdAndWithoutPohodaIdFromDate(DateTime $fromDate): array
    {
        return $this->orderRepository->getOrdersWithLegacyIdAndWithoutPohodaIdFromDate($fromDate);
    }

    /**
     * This method is copy-pasted from vendor, but the whole identity map is flushed here instead of the provided array
     * It improves the performance, see https://github.com/shopsys/shopsys/pull/2080
     *
     * @param \App\Model\Order\OrderData $orderData
     * @param \App\Model\Order\Preview\OrderPreview $orderPreview
     * @param \App\Model\Customer\User\CustomerUser|null $customerUser
     *
     * @return \App\Model\Order\Order
     */
    public function createOrder(BaseOrderData $orderData, BaseOrderPreview $orderPreview, ?BaseCustomerUser $customerUser = null)
    {
        $orderNumber = $this->orderNumberSequenceRepository->getNextNumber();
        $orderUrlHash = $this->orderHashGeneratorRepository->getUniqueHash();

        $this->setOrderDataAdministrator($orderData);

        /** @var \App\Model\Order\Order $order */
        $order = $this->orderFactory->create(
            $orderData,
            (string)$orderNumber,
            $orderUrlHash,
            $customerUser
        );

        $this->fillOrderItems($order, $orderPreview);

        foreach ($order->getItems() as $orderItem) {
            $this->em->persist($orderItem);
        }

        $order->setTotalPrice(
            $this->orderPriceCalculation->getOrderTotalPrice($order)
        );

        $this->em->persist($order);
        $this->em->flush();

        return $order;
    }
}
