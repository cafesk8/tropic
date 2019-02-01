<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order;

use GoPay\Definition\Response\PaymentStatus;
use GoPay\Http\Response;
use Shopsys\FrameworkBundle\Model\Order\OrderFacade as BaseOrderFacade;

class OrderFacade extends BaseOrderFacade
{
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
     * @param \DateTime $fromDate
     * @return \Shopsys\ShopBundle\Model\Order\Order[]
     */
    public function getAllUnpaidGoPayOrders(\DateTime $fromDate): array
    {
        return $this->orderRepository->getAllUnpaidGoPayOrders($fromDate);
    }
}
