<?php

declare(strict_types=1);

namespace App\Model\Order;

use App\Component\Domain\DomainHelper;
use App\Component\Mall\MallImportOrderClient;
use App\Component\SmsManager\SmsManagerFactory;
use App\Component\SmsManager\SmsMessageFactory;
use App\Model\Gtm\GtmHelper;
use App\Model\Order\Item\OrderItemFactory;
use App\Model\Order\Mall\Exception\StatusChangException;
use App\Model\Order\PromoCode\PromoCode;
use App\Model\Order\PromoCode\PromoCodeData;
use App\Model\Product\Gift\ProductGiftPriceCalculation;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Setting\Setting;
use Shopsys\FrameworkBundle\Model\Administrator\Security\AdministratorFrontSecurityFacade;
use Shopsys\FrameworkBundle\Model\Cart\CartFacade;
use Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserFacade;
use Shopsys\FrameworkBundle\Model\Heureka\HeurekaFacade;
use Shopsys\FrameworkBundle\Model\Localization\Localization;
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
use Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreview;
use Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreviewFactory;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\CurrentPromoCodeFacade;
use Shopsys\FrameworkBundle\Model\Order\Status\OrderStatusRepository;
use Shopsys\FrameworkBundle\Model\Payment\PaymentPriceCalculation;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Pricing\Vat\VatFacade;
use Shopsys\FrameworkBundle\Model\Transport\TransportPriceCalculation;
use Shopsys\FrameworkBundle\Twig\NumberFormatterExtension;

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
 * @method \App\Model\Order\Order createOrder(\App\Model\Order\OrderData $orderData, \App\Model\Order\Preview\OrderPreview $orderPreview, \App\Model\Customer\User\CustomerUser|null $customerUser)
 * @method prefillFrontOrderData(\App\Model\Order\FrontOrderData $orderData, \App\Model\Customer\User\CustomerUser $customerUser)
 * @method \App\Model\Order\Order[] getCustomerUserOrderList(\App\Model\Customer\User\CustomerUser $customerUser)
 * @method \App\Model\Order\Order[] getOrderListForEmailByDomainId(string $email, int $domainId)
 * @method \App\Model\Order\Order getById(int $orderId)
 * @method \App\Model\Order\Order getByUrlHashAndDomain(string $urlHash, int $domainId)
 * @method \App\Model\Order\Order getByOrderNumberAndUser(string $orderNumber, \App\Model\Customer\User\CustomerUser $customerUser)
 * @method setOrderDataAdministrator(\App\Model\Order\OrderData $orderData)
 * @method calculateOrderItemDataPrices(\App\Model\Order\Item\OrderItemData $orderItemData, int $domainId)
 * @method sendHeurekaOrderInfo(\App\Model\Order\Order $order, bool $disallowHeurekaVerifiedByCustomers)
 * @method fillOrderPayment(\App\Model\Order\Order $order, \App\Model\Order\Preview\OrderPreview $orderPreview, string $locale)
 * @method fillOrderTransport(\App\Model\Order\Order $order, \App\Model\Order\Preview\OrderPreview $orderPreview, string $locale)
 * @method fillOrderRounding(\App\Model\Order\Order $order, \App\Model\Order\Preview\OrderPreview $orderPreview, string $locale)
 * @method refreshOrderItemsWithoutTransportAndPayment(\App\Model\Order\Order $order, \App\Model\Order\OrderData $orderData)
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
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Vat\VatFacade
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
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderNumberSequenceRepository $orderNumberSequenceRepository
     * @param \App\Model\Order\OrderRepository $orderRepository
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderUrlGenerator $orderUrlGenerator
     * @param \Shopsys\FrameworkBundle\Model\Order\Status\OrderStatusRepository $orderStatusRepository
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
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\OrderProductFacade $orderProductFacade
     * @param \Shopsys\FrameworkBundle\Model\Heureka\HeurekaFacade $heurekaFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderFactoryInterface $orderFactory
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderPriceCalculation $orderPriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\OrderItemPriceCalculation $orderItemPriceCalculation
     * @param \App\Model\Order\FrontOrderDataMapper $frontOrderDataMapper
     * @param \App\Twig\NumberFormatterExtension $numberFormatterExtension
     * @param \App\Model\Payment\PaymentPriceCalculation $paymentPriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Transport\TransportPriceCalculation $transportPriceCalculation
     * @param \App\Model\Order\Item\OrderItemFactory $orderItemFactory
     * @param \App\Model\Product\Gift\ProductGiftPriceCalculation $productGiftPriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Vat\VatFacade $vatFacade
     * @param \App\Component\Mall\MallImportOrderClient $mallImportOrderClient
     * @param \App\Model\Gtm\GtmHelper $gtmHelper
     * @param \App\Component\SmsManager\SmsManagerFactory $smsManagerFactory
     * @param \App\Component\SmsManager\SmsMessageFactory $smsMessageFactory
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
     * @return \App\Model\Order\Order
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

        /** @var \App\Model\Order\Order $order */
        $order = parent::createOrderFromFront($orderData);
        $this->orderProductFacade->subtractOrderProductsFromStock($order->getGiftItems());

        /** @var \App\Model\Customer\User\CustomerUser $customer */
        $customer = $order->getCustomerUser();
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
        /** @var \App\Model\Order\Order $order */
        $order = $this->getById($orderId);
        $order->markAsExported();

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
    protected function fillOrderItems(BaseOrder $order, OrderPreview $orderPreview): void
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
            $this->sendSms($updatedOrder);
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
    public function setCustomerToOrder(BaseOrder $order, CustomerUser $customer): void
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
                $product->getVatForDomain($order->getDomainId())->getPercent(),
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
            $this->vatFacade->getDefaultVatForDomain($order->getDomainId())->getPercent()
        );
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param \App\Model\Order\Preview\OrderPreview $orderPreview
     */
    private function fillOrderPromoProducts(BaseOrder $order, OrderPreview $orderPreview): void
    {
        /** @var \App\Model\Cart\Item\CartItem $promoProductCartItem */
        foreach ($orderPreview->getPromoProductCartItems() as $promoProductCartItem) {
            $product = $promoProductCartItem->getProduct();
            $promoProduct = $promoProductCartItem->getPromoProduct();

            if (!$this->orderItemFactory instanceof OrderItemFactory) {
                $message = 'Object "' . get_class($this->orderItemFactory) . '" has to be instance of \App\Model\Order\Item\OrderItemFactory.';
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
                $product->getVatForDomain($order->getDomainId())->getPercent(),
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
     * @param \App\Model\Order\Order $order
     * @param \App\Model\Order\Preview\OrderPreview $orderPreview
     */
    private function fillOrderGifts(BaseOrder $order, OrderPreview $orderPreview): void
    {
        /** @var \App\Model\Cart\Item\CartItem $giftInCart */
        foreach ($orderPreview->getGifts() as $giftInCart) {
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
    }
}
