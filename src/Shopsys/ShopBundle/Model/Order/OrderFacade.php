<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use GoPay\Definition\Response\PaymentStatus;
use GoPay\Http\Response;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Setting\Setting;
use Shopsys\FrameworkBundle\Model\Administrator\Security\AdministratorFrontSecurityFacade;
use Shopsys\FrameworkBundle\Model\Cart\CartFacade;
use Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer;
use Shopsys\FrameworkBundle\Model\Customer\CustomerFacade;
use Shopsys\FrameworkBundle\Model\Heureka\HeurekaFacade;
use Shopsys\FrameworkBundle\Model\Localization\Localization;
use Shopsys\FrameworkBundle\Model\Order\FrontOrderDataMapper;
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
use Shopsys\FrameworkBundle\Model\Pricing\Vat\VatFacade;
use Shopsys\FrameworkBundle\Model\Transport\TransportPriceCalculation;
use Shopsys\FrameworkBundle\Twig\NumberFormatterExtension;
use Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeData;
use Shopsys\ShopBundle\Model\Product\Gift\ProductGiftPriceCalculation;

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
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Vat\VatFacade
     */
    private $vatFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderNumberSequenceRepository $orderNumberSequenceRepository
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderRepository $orderRepository
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderUrlGenerator $orderUrlGenerator
     * @param \Shopsys\FrameworkBundle\Model\Order\Status\OrderStatusRepository $orderStatusRepository
     * @param \Shopsys\FrameworkBundle\Model\Order\Mail\OrderMailFacade $orderMailFacade
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderHashGeneratorRepository $orderHashGeneratorRepository
     * @param \Shopsys\FrameworkBundle\Component\Setting\Setting $setting
     * @param \Shopsys\FrameworkBundle\Model\Localization\Localization $localization
     * @param \Shopsys\FrameworkBundle\Model\Administrator\Security\AdministratorFrontSecurityFacade $administratorFrontSecurityFacade
     * @param \Shopsys\FrameworkBundle\Model\Order\PromoCode\CurrentPromoCodeFacade $currentPromoCodeFacade
     * @param \Shopsys\FrameworkBundle\Model\Cart\CartFacade $cartFacade
     * @param \Shopsys\FrameworkBundle\Model\Customer\CustomerFacade $customerFacade
     * @param \Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer $currentCustomer
     * @param \Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreviewFactory $orderPreviewFactory
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\OrderProductFacade $orderProductFacade
     * @param \Shopsys\FrameworkBundle\Model\Heureka\HeurekaFacade $heurekaFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderFactoryInterface $orderFactory
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderPriceCalculation $orderPriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\OrderItemPriceCalculation $orderItemPriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Order\FrontOrderDataMapper $frontOrderDataMapper
     * @param \Shopsys\FrameworkBundle\Twig\NumberFormatterExtension $numberFormatterExtension
     * @param \Shopsys\FrameworkBundle\Model\Payment\PaymentPriceCalculation $paymentPriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Transport\TransportPriceCalculation $transportPriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\OrderItemFactoryInterface $orderItemFactory
     * @param \Shopsys\ShopBundle\Model\Product\Gift\ProductGiftPriceCalculation $productGiftPriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Vat\VatFacade $vatFacade
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
        VatFacade $vatFacade
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

        if ($order->getGoPayId() !== null && $order->getGoPayStatus() === PaymentStatus::PAID) {
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
     * @param string $goPayId
     */
    public function setGoPayId(Order $order, string $goPayId): void
    {
        $order->setGoPayId($goPayId);
        $this->em->flush($order);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @param \GoPay\Http\Response $goPayStatusResponse
     */
    public function setGoPayStatusAndFik(Order $order, Response $goPayStatusResponse): void
    {
        if (array_key_exists('eet_code', $goPayStatusResponse->json)) {
            $order->setGoPayFik($goPayStatusResponse->json['eet_code']['fik']);
        }

        $order->setGoPayStatus($goPayStatusResponse->json['state']);
        $this->em->flush($order);
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
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderData $orderData
     * @return \Shopsys\FrameworkBundle\Model\Order\Order
     */
    public function createOrderFromFront(BaseOrderData $orderData): BaseOrder
    {
        $enteredValidPromoCode = $this->currentPromoCodeFacade->getValidEnteredPromoCodeOrNull();

        /** @var \Shopsys\ShopBundle\Model\Order\Order $order */
        $order = parent::createOrderFromFront($orderData);
        $this->orderProductFacade->subtractOrderProductsFromStock($order->getGiftItems());

        if ($enteredValidPromoCode !== null) {
            $this->currentPromoCodeFacade->usePromoCode($enteredValidPromoCode);
        }

        /** @var \Shopsys\ShopBundle\Model\Customer\User $customer */
        $customer = $order->getCustomer();
        if ($customer !== null) {
            $order->setCustomerTransferId($customer->getTransferId());
            $this->em->flush($order);
        }

        return $order;
    }

    /**
     * @param \DateTime $startTime
     * @param \DateTime $endTime
     * @return int[]
     */
    public function getCustomerIdsFromOrdersUpdatedAt(DateTime $startTime, DateTime $endTime): array
    {
        return $this->orderRepository->getCustomerIdsFromOrdersUpdatedAt($startTime, $endTime);
    }

    /**
     * @param int[] $customerIds
     * @return \Shopsys\FrameworkBundle\Component\Money\Money[]
     */
    public function getOrdersValueIndexedByCustomerId(array $customerIds): array
    {
        return $this->orderRepository->getOrdersValueIndexedByCustomerId($customerIds);
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
    public function getNotExportedOrdersBatch(int $limit): array
    {
        return $this->orderRepository->getNotExportedOrdersBatch($limit);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @param \Shopsys\ShopBundle\Model\Order\Preview\OrderPreview $orderPreview
     */
    protected function fillOrderItems(Order $order, OrderPreview $orderPreview): void
    {
        parent::fillOrderItems($order, $orderPreview);

        $order->fillOrderGifts($orderPreview, $this->orderItemFactory, $this->productGiftPriceCalculation, $this->domain);

        $promoCode = $orderPreview->getPromoCode();
        if ($promoCode !== null && $promoCode->getType() === PromoCodeData::TYPE_CERTIFICATE) {
            $order->setGiftCertificate(
                $orderPreview,
                $this->orderItemFactory,
                $this->numberFormatterExtension,
                $order,
                $promoCode,
                $this->vatFacade->getDefaultVat()->getPercent(),
                $this->domain->getCurrentDomainConfig()->getLocale()
            );
        }
    }
}
