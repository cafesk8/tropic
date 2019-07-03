<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order;

use DateTime;
use GoPay\Definition\Response\PaymentStatus;
use GoPay\Http\Response;
use Shopsys\FrameworkBundle\Model\Order\Order as BaseOrder;
use Shopsys\FrameworkBundle\Model\Order\OrderData as BaseOrderData;
use Shopsys\FrameworkBundle\Model\Order\OrderFacade as BaseOrderFacade;

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
     * @var \Shopsys\ShopBundle\Model\Order\OrderRepository
     */
    protected $orderRepository;

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
        $this->currentPromoCodeFacade->useEnteredPromoCode();

        $order = parent::createOrderFromFront($orderData);

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
     * @return \Shopsys\ShopBundle\Model\Order\Order[]
     */
    public function getNotExportedOrders(): array
    {
        return $this->orderRepository->getNotExportedOrders();
    }
}
