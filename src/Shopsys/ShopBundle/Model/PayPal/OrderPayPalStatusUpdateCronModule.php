<?php

declare(strict_types = 1);

namespace Shopsys\ShopBundle\Model\PayPal;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\Plugin\Cron\SimpleCronModuleInterface;
use Shopsys\ShopBundle\Model\Order\OrderFacade;
use Symfony\Bridge\Monolog\Logger;

class OrderPayPalStatusUpdateCronModule implements SimpleCronModuleInterface
{
    /**
     * @var \Symfony\Bridge\Monolog\Logger
     */
    private $logger;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var \Shopsys\ShopBundle\Model\Order\OrderFacade
     */
    private $orderFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\PayPal\PayPalFacade
     */
    private $payPalFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\ShopBundle\Model\Order\OrderFacade $orderFacade
     * @param \Shopsys\ShopBundle\Model\PayPal\PayPalFacade $payPalFacade
     */
    public function __construct(
        EntityManagerInterface $em,
        OrderFacade $orderFacade,
        PayPalFacade $payPalFacade
    ) {
        $this->em = $em;
        $this->orderFacade = $orderFacade;
        $this->payPalFacade = $payPalFacade;
    }

    public function run(): void
    {
        $twentyOneDaysAgo = new DateTime('-21 days');
        $orders = $this->orderFacade->getAllUnpaidPayPalOrders($twentyOneDaysAgo);

        $this->logger->debug('Downloading status updates for ' . count($orders) . ' orders.');

        foreach ($orders as $order) {
            $this->logger->debug('Downloading PayPal status for order with ID "' . $order->getId() . '".');

            $oldPayPalStatus = $order->getPayPalStatus();
            $newPayPalStatus = $this->payPalFacade->getPaymentStatus($order->getPayPalId());

            if ($oldPayPalStatus !== $newPayPalStatus) {
                $this->orderFacade->setPayPalStatus($order, $newPayPalStatus);
                $this->logger->info('Order with id "' . $order->getId() . '" changed PayPal status from "' . $oldPayPalStatus . '" to "' . $newPayPalStatus . '".');
            }
        }

        $this->em->flush();
    }

    /**
     * @param \Symfony\Bridge\Monolog\Logger $logger
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }
}
