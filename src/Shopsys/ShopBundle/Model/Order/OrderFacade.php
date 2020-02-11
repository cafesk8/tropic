<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Setting\Setting;
use Shopsys\FrameworkBundle\Model\Administrator\Security\AdministratorFrontSecurityFacade;
use Shopsys\FrameworkBundle\Model\Cart\CartFacade;
use Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer;
use Shopsys\FrameworkBundle\Model\Customer\CustomerFacade;
use Shopsys\FrameworkBundle\Model\Customer\User;
use Shopsys\FrameworkBundle\Model\Heureka\HeurekaFacade;
use Shopsys\FrameworkBundle\Model\Localization\Localization;
use Shopsys\FrameworkBundle\Model\Order\FrontOrderDataMapper;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItem;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItemFactoryInterface;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItemPriceCalculation;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderProductFacade;
use Shopsys\FrameworkBundle\Model\Order\Mail\OrderMailFacade;
use Shopsys\FrameworkBundle\Model\Order\Order;
use Shopsys\FrameworkBundle\Model\Order\Order as BaseOrder;
use Shopsys\FrameworkBundle\Model\Order\OrderData as BaseOrderData;
use Shopsys\FrameworkBundle\Model\Order\OrderFacade as BaseOrderFacade;
use Shopsys\FrameworkBundle\Model\Order\OrderFactoryInterface;
use Shopsys\FrameworkBundle\Model\Order\OrderHashGeneratorRepository;
use Shopsys\FrameworkBundle\Model\Order\OrderNumberSequenceRepository;
use Shopsys\FrameworkBundle\Model\Order\OrderPriceCalculation;
use Shopsys\FrameworkBundle\Model\Order\OrderRepository;
use Shopsys\FrameworkBundle\Model\Order\OrderUrlGenerator;
use Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreview;
use Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreviewFactory;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\CurrentPromoCodeFacade;
use Shopsys\FrameworkBundle\Model\Order\Status\OrderStatusRepository;
use Shopsys\FrameworkBundle\Model\Payment\PaymentPriceCalculation;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Pricing\Vat\VatFacade;
use Shopsys\FrameworkBundle\Model\Transport\TransportPriceCalculation;
use Shopsys\FrameworkBundle\Twig\NumberFormatterExtension;
use Shopsys\ShopBundle\Component\Domain\DomainHelper;
use Shopsys\ShopBundle\Component\Mall\MallImportOrderClient;
use Shopsys\ShopBundle\Component\SmsManager\SmsManagerFactory;
use Shopsys\ShopBundle\Component\SmsManager\SmsMessageFactory;
use Shopsys\ShopBundle\Model\Gtm\GtmHelper;
use Shopsys\ShopBundle\Model\Order\Item\OrderItemFactory;
use Shopsys\ShopBundle\Model\Order\Mall\Exception\StatusChangException;
use Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode;
use Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeData;
use Shopsys\ShopBundle\Model\Product\Gift\ProductGiftPriceCalculation;

/**
 * @property \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
 * @property \Shopsys\ShopBundle\Component\Setting\Setting $setting
 * @property \Shopsys\ShopBundle\Model\Cart\CartFacade $cartFacade
 * @property \Shopsys\ShopBundle\Model\Customer\CustomerFacade $customerFacade
 * @property \Shopsys\ShopBundle\Model\Order\Preview\OrderPreviewFactory $orderPreviewFactory
 * @property \Shopsys\ShopBundle\Model\Order\FrontOrderDataMapper $frontOrderDataMapper
 * @property \Shopsys\ShopBundle\Twig\NumberFormatterExtension $numberFormatterExtension
 * @property \Shopsys\ShopBundle\Model\Payment\PaymentPriceCalculation $paymentPriceCalculation
 * @property \Shopsys\ShopBundle\Model\Order\Item\OrderItemFactory $orderItemFactory
 * @method \Shopsys\ShopBundle\Model\Order\Order createOrder(\Shopsys\ShopBundle\Model\Order\OrderData $orderData, \Shopsys\ShopBundle\Model\Order\Preview\OrderPreview $orderPreview, \Shopsys\ShopBundle\Model\Customer\User|null $user)
 * @method sendHeurekaOrderInfo(\Shopsys\ShopBundle\Model\Order\Order $order, bool $disallowHeurekaVerifiedByCustomers)
 * @method prefillFrontOrderData(\Shopsys\ShopBundle\Model\Order\FrontOrderData $orderData, \Shopsys\ShopBundle\Model\Customer\User $user)
 * @method \Shopsys\ShopBundle\Model\Order\Order[] getCustomerOrderList(\Shopsys\ShopBundle\Model\Customer\User $user)
 * @method \Shopsys\ShopBundle\Model\Order\Order[] getOrderListForEmailByDomainId(string $email, int $domainId)
 * @method \Shopsys\ShopBundle\Model\Order\Order getById(int $orderId)
 * @method \Shopsys\ShopBundle\Model\Order\Order getByUrlHashAndDomain(string $urlHash, int $domainId)
 * @method \Shopsys\ShopBundle\Model\Order\Order getByOrderNumberAndUser(string $orderNumber, \Shopsys\ShopBundle\Model\Customer\User $user)
 * @method setOrderDataAdministrator(\Shopsys\ShopBundle\Model\Order\OrderData $orderData)
 * @method fillOrderPayment(\Shopsys\ShopBundle\Model\Order\Order $order, \Shopsys\ShopBundle\Model\Order\Preview\OrderPreview $orderPreview, string $locale)
 * @method fillOrderTransport(\Shopsys\ShopBundle\Model\Order\Order $order, \Shopsys\ShopBundle\Model\Order\Preview\OrderPreview $orderPreview, string $locale)
 * @method fillOrderRounding(\Shopsys\ShopBundle\Model\Order\Order $order, \Shopsys\ShopBundle\Model\Order\Preview\OrderPreview $orderPreview, string $locale)
 * @method refreshOrderItemsWithoutTransportAndPayment(\Shopsys\ShopBundle\Model\Order\Order $order, \Shopsys\ShopBundle\Model\Order\OrderData $orderData)
 * @method calculateOrderItemDataPrices(\Shopsys\ShopBundle\Model\Order\Item\OrderItemData $orderItemData)
 */
class OrderFacade extends BaseOrderFacade
{
    /**
     * @var \Shopsys\ShopBundle\Model\Order\OrderRepository
     */
    protected $orderRepository;

    /**
     * @var \Shopsys\ShopBundle\Model\Order\PromoCode\CurrentPromoCodeFacade
     */
    protected $currentPromoCodeFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Gift\ProductGiftPriceCalculation
     */
    private $productGiftPriceCalculation;

    /**
     * @var \Shopsys\ShopBundle\Component\Mall\MallImportOrderClient
     */
    private $mallImportOrderClient;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Vat\VatFacade
     */
    private $vatFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Gtm\GtmHelper
     */
    private $gtmHelper;

    /**
     * @var \Shopsys\ShopBundle\Component\SmsManager\SmsManagerFactory
     */
    private $smsManagerFactory;

    /**
     * @var \Shopsys\ShopBundle\Component\SmsManager\SmsMessageFactory
     */
    private $smsMessageFactory;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderNumberSequenceRepository $orderNumberSequenceRepository
     * @param \Shopsys\ShopBundle\Model\Order\OrderRepository $orderRepository
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderUrlGenerator $orderUrlGenerator
     * @param \Shopsys\FrameworkBundle\Model\Order\Status\OrderStatusRepository $orderStatusRepository
     * @param \Shopsys\FrameworkBundle\Model\Order\Mail\OrderMailFacade $orderMailFacade
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderHashGeneratorRepository $orderHashGeneratorRepository
     * @param \Shopsys\ShopBundle\Component\Setting\Setting $setting
     * @param \Shopsys\FrameworkBundle\Model\Localization\Localization $localization
     * @param \Shopsys\FrameworkBundle\Model\Administrator\Security\AdministratorFrontSecurityFacade $administratorFrontSecurityFacade
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\CurrentPromoCodeFacade $currentPromoCodeFacade
     * @param \Shopsys\ShopBundle\Model\Cart\CartFacade $cartFacade
     * @param \Shopsys\ShopBundle\Model\Customer\CustomerFacade $customerFacade
     * @param \Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer $currentCustomer
     * @param \Shopsys\ShopBundle\Model\Order\Preview\OrderPreviewFactory $orderPreviewFactory
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\OrderProductFacade $orderProductFacade
     * @param \Shopsys\FrameworkBundle\Model\Heureka\HeurekaFacade $heurekaFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderFactoryInterface $orderFactory
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderPriceCalculation $orderPriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\OrderItemPriceCalculation $orderItemPriceCalculation
     * @param \Shopsys\ShopBundle\Model\Order\FrontOrderDataMapper $frontOrderDataMapper
     * @param \Shopsys\ShopBundle\Twig\NumberFormatterExtension $numberFormatterExtension
     * @param \Shopsys\ShopBundle\Model\Payment\PaymentPriceCalculation $paymentPriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Transport\TransportPriceCalculation $transportPriceCalculation
     * @param \Shopsys\ShopBundle\Model\Order\Item\OrderItemFactory $orderItemFactory
     * @param \Shopsys\ShopBundle\Model\Product\Gift\ProductGiftPriceCalculation $productGiftPriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Vat\VatFacade $vatFacade
     * @param \Shopsys\ShopBundle\Component\Mall\MallImportOrderClient $mallImportOrderClient
     * @param \Shopsys\ShopBundle\Model\Gtm\GtmHelper $gtmHelper
     * @param \Shopsys\ShopBundle\Component\SmsManager\SmsManagerFactory $smsManagerFactory
     * @param \Shopsys\ShopBundle\Component\SmsManager\SmsMessageFactory $smsMessageFactory
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
        CustomerFacade $customerFacade,
        CurrentCustomer $currentCustomer,
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
        SmsMessageFactory $smsMessageFactory
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
            $customerFacade,
            $currentCustomer,
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
    }

    /**
     * @param int $orderId
     * @return string
     */
    public function getOrderSentPageContent($orderId): string
    {
        /** @var \Shopsys\ShopBundle\Model\Order\Order $order */
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
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @param string $payPalStatus
     */
    public function setPayPalStatus(Order $order, string $payPalStatus): void
    {
        $order->setPayPalStatus($payPalStatus);
        $this->em->flush($order);
    }

    /**
     * @param \DateTime $fromDate
     * @return \Shopsys\ShopBundle\Model\Order\Order[]
     */
    public function getAllUnpaidGoPayOrders(\DateTime $fromDate): array
    {
        return $this->orderRepository->getAllUnpaidGoPayOrders($fromDate);
    }

    /**
     * @param \DateTime $fromDate
     * @return \Shopsys\ShopBundle\Model\Order\Order[]
     */
    public function getAllUnpaidPayPalOrders(\DateTime $fromDate): array
    {
        return $this->orderRepository->getAllUnpaidPayPalOrders($fromDate);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\OrderData $orderData
     * @return \Shopsys\ShopBundle\Model\Order\Order
     */
    public function createOrderFromFront(BaseOrderData $orderData): BaseOrder
    {
        $validEnteredPromoCodes = $this->currentPromoCodeFacade->getValidEnteredPromoCodes();
        $orderPreview = $this->orderPreviewFactory->createForCurrentUser($orderData->transport, $orderData->payment);
        $this->gtmHelper->amendGtmCouponToOrderData($orderData, $validEnteredPromoCodes, $orderPreview);

        foreach ($validEnteredPromoCodes as $validEnteredPromoCode) {
            $orderData->promoCodesCodes[] = $validEnteredPromoCode->getCode();
            $this->currentPromoCodeFacade->usePromoCode($validEnteredPromoCode);
        }

        /** @var \Shopsys\ShopBundle\Model\Order\Order $order */
        $order = parent::createOrderFromFront($orderData);
        $this->orderProductFacade->subtractOrderProductsFromStock($order->getGiftItems());

        /** @var \Shopsys\ShopBundle\Model\Customer\User $customer */
        $customer = $order->getCustomer();
        if ($customer !== null) {
            $order->setCustomerTransferId($customer->getTransferId());
            $order->setMemberOfLoyaltyProgram($customer->isMemberOfLoyaltyProgram());
            $this->em->flush($order);
        }

        return $order;
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
     */
    public function markOrderAsExported(int $orderId): void
    {
        /** @var \Shopsys\ShopBundle\Model\Order\Order $order */
        $order = $this->getById($orderId);
        $order->markAsExported();

        $this->em->flush($order);
    }

    /**
     * @param int $orderId
     */
    public function markOrderAsFailedExported(int $orderId): void
    {
        /** @var \Shopsys\ShopBundle\Model\Order\Order $order */
        $order = $this->getById($orderId);
        $order->markAsFailedExported();

        $this->em->flush($order);
    }

    /**
     * @param int $limit
     * @return \Shopsys\ShopBundle\Model\Order\Order[]
     */
    public function getReadyOrdersForExportBatch(int $limit): array
    {
        return $this->orderRepository->getReadyOrdersForExportBatch($limit);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @param \Shopsys\ShopBundle\Model\Order\Preview\OrderPreview $orderPreview
     */
    protected function fillOrderItems(Order $order, OrderPreview $orderPreview): void
    {
        parent::fillOrderItems($order, $orderPreview);

        $this->fillOrderGifts($order, $orderPreview);
        $this->fillOrderPromoProducts($order, $orderPreview);

        $promoCodes = $orderPreview->getPromoCodesIndexedById();
        foreach ($promoCodes as $promoCode) {
            if ($promoCode->getType() === PromoCodeData::TYPE_CERTIFICATE) {
                $this->setGiftCertificate(
                    $orderPreview,
                    $order,
                    $promoCode
                );
            }
        }
    }

    /**
     * @param string $number
     * @return \Shopsys\ShopBundle\Model\Order\Order|null
     */
    public function findByNumber(string $number): ?Order
    {
        return $this->orderRepository->findByNumber($number);
    }

    /**
     * @param int $limit
     * @return \Shopsys\ShopBundle\Model\Order\Order[]
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
     * @param \Shopsys\ShopBundle\Model\Order\OrderData $orderData
     * @param string|null $locale
     * @return \Shopsys\ShopBundle\Model\Order\Order
     */
    public function edit($orderId, BaseOrderData $orderData, ?string $locale = null)
    {
        /** @var \Shopsys\ShopBundle\Model\Order\Order $order */
        $order = $this->orderRepository->getById($orderId);
        $originalMallStatus = $order->getMallStatus();
        $originalOrderStatus = $order->getStatus();
        $orderData->orderPayment->name = $orderData->orderPayment->payment->getName($locale);
        $orderData->orderTransport->name = $orderData->orderTransport->transport->getName($locale);
        /** @var \Shopsys\ShopBundle\Model\Order\Order $updatedOrder */
        $updatedOrder = parent::edit($orderId, $orderData);

        if ($originalMallStatus !== $updatedOrder->getMallStatus()) {
            try {
                $this->mallImportOrderClient->changeStatus((int)$updatedOrder->getMallOrderId(), $originalMallStatus, $updatedOrder->getMallStatus());
            } catch (Exception $ex) {
                throw new StatusChangException($ex);
            }
        }

        if ($originalOrderStatus !== $updatedOrder->getStatus()) {
            $this->sendSms($updatedOrder);
        }

        return $updatedOrder;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     */
    public function sendSms(Order $order): void
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
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @param \Shopsys\ShopBundle\Model\Customer\User $customer
     */
    public function setCustomerToOrder(Order $order, User $customer): void
    {
        $order->setCustomer($customer);
        $this->em->flush($order);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @param \Shopsys\ShopBundle\Model\Order\Preview\OrderPreview $orderPreview
     * @param string $locale
     */
    public function fillOrderProducts(
        Order $order,
        OrderPreview $orderPreview,
        string $locale
    ): void {
        $quantifiedItemPrices = $orderPreview->getQuantifiedItemsPrices();
        $quantifiedItemDiscountsIndexedByPromoCodeId = $orderPreview->getQuantifiedItemsDiscountsIndexedByPromoCodeId();

        foreach ($orderPreview->getQuantifiedProducts() as $index => $quantifiedProduct) {
            $product = $quantifiedProduct->getProduct();

            $quantifiedItemPrice = $quantifiedItemPrices[$index];
            /* @var $quantifiedItemPrice \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice */

            $orderItem = $this->orderItemFactory->createProduct(
                $order,
                $product->getName($locale),
                $quantifiedItemPrice->getUnitPrice(),
                $product->getVat()->getPercent(),
                $quantifiedProduct->getQuantity(),
                $product->getUnit()->getName($locale),
                $product->getCatnum(),
                $product
            );

            foreach ($quantifiedItemDiscountsIndexedByPromoCodeId as $promoCodeId => $quantifiedItemDiscounts) {
                $quantifiedItemDiscount = $quantifiedItemDiscounts[$index];
                /* @var $quantifiedItemDiscount \Shopsys\FrameworkBundle\Model\Pricing\Price|null */
                if ($quantifiedItemDiscount !== null) {
                    $promoCode = $orderPreview->getPromoCodeById($promoCodeId);
                    $this->addOrderItemDiscount($orderItem, $quantifiedItemDiscount, $locale, (float)$orderPreview->getPromoCodeDiscountPercent(), $promoCode);
                }
            }
        }
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Item\OrderItem $orderItem
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $quantifiedItemDiscount
     * @param string $locale
     * @param float $discountPercent
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode|null $promoCode
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
            '%s %s - %s',
            t('Promo code', [], 'messages', $locale),
            $discountValue,
            $orderItem->getName()
        );

        $this->orderItemFactory->createPromoCode(
            $name,
            $quantifiedItemDiscount->inverse(),
            $orderItem
        );
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Preview\OrderPreview $orderPreview
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode $promoCode
     * @throws \Shopsys\FrameworkBundle\Component\Domain\Exception\NoDomainSelectedException
     */
    private function setGiftCertificate(
        OrderPreview $orderPreview,
        Order $order,
        PromoCode $promoCode
    ): void {
        $locale = $this->domain->getCurrentDomainConfig()->getLocale();
        $name = sprintf(
            '%s %s %s',
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
            $this->vatFacade->getDefaultVat()->getPercent()
        );
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @param \Shopsys\ShopBundle\Model\Order\Preview\OrderPreview $orderPreview
     */
    private function fillOrderPromoProducts(Order $order, OrderPreview $orderPreview): void
    {
        /** @var \Shopsys\ShopBundle\Model\Cart\Item\CartItem $promoProductCartItem */
        foreach ($orderPreview->getPromoProductCartItems() as $promoProductCartItem) {
            $product = $promoProductCartItem->getProduct();
            $promoProduct = $promoProductCartItem->getPromoProduct();

            if (!$this->orderItemFactory instanceof OrderItemFactory) {
                $message = 'Object "' . get_class($this->orderItemFactory) . '" has to be instance of \Shopsys\ShopBundle\Model\Order\Item\OrderItemFactory.';
                throw new \Symfony\Component\Config\Definition\Exception\InvalidTypeException($message);
            }

            $promoProductOrderItemPrice = new Price($promoProductCartItem->getWatchedPrice(), $promoProductCartItem->getWatchedPrice());
            $promoProductOrderItemTotalPrice = new Price(
                $promoProductCartItem->getWatchedPrice()->multiply($promoProductCartItem->getQuantity()),
                $promoProductCartItem->getWatchedPrice()->multiply($promoProductCartItem->getQuantity())
            );

            $this->orderItemFactory->createPromoProduct(
                $order,
                $product->getName($this->domain->getLocale()),
                $promoProductOrderItemPrice,
                $product->getVat()->getPercent(),
                $promoProductCartItem->getQuantity(),
                $product->getUnit()->getName($this->domain->getLocale()),
                $product->getCatnum(),
                $product,
                $promoProductOrderItemTotalPrice,
                $promoProduct
            );
        }
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @param \Shopsys\ShopBundle\Model\Order\Preview\OrderPreview $orderPreview
     */
    private function fillOrderGifts(Order $order, OrderPreview $orderPreview): void
    {
        /** @var \Shopsys\ShopBundle\Model\Cart\Item\CartItem $giftInCart */
        foreach ($orderPreview->getGifts() as $giftInCart) {
            $gift = $giftInCart->getProduct();

            if (!$this->orderItemFactory instanceof OrderItemFactory) {
                $message = 'Object "' . get_class($this->orderItemFactory) . '" has to be instance of \Shopsys\ShopBundle\Model\Order\Item\OrderItemFactory.';
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
                $gift->getVat()->getPercent(),
                $giftInCart->getQuantity(),
                $gift->getUnit()->getName($this->domain->getLocale()),
                $gift->getCatnum(),
                $gift,
                $giftTotalPrice
            );
        }
    }
}
