<?php

declare(strict_types = 1);

namespace Shopsys\ShopBundle\Model\GoPay;

use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use GoPay\Definition\Response\PaymentStatus;
use Shopsys\FrameworkBundle\Model\Order\Mail\OrderMailFacade;
use Shopsys\Plugin\Cron\SimpleCronModuleInterface;
use Shopsys\ShopBundle\Model\GoPay\Exception\GoPayPaymentDownloadException;
use Shopsys\ShopBundle\Model\Order\OrderFacade;
use Symfony\Bridge\Monolog\Logger;

class OrderGoPayStatusUpdateCronModule implements SimpleCronModuleInterface
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
     * @var \Shopsys\FrameworkBundle\Model\Order\Mail\OrderMailFacade
     */
    private $orderMailFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\GoPay\GoPayFacadeOnCurrentDomain
     */
    private $goPayFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\ShopBundle\Model\Order\OrderFacade $orderFacade
     * @param \Shopsys\FrameworkBundle\Model\Order\Mail\OrderMailFacade $orderMailFacade
     * @param \Shopsys\ShopBundle\Model\GoPay\GoPayFacadeOnCurrentDomain $goPayFacade
     */
    public function __construct(
        EntityManagerInterface $em,
        OrderFacade $orderFacade,
        OrderMailFacade $orderMailFacade,
        GoPayFacadeOnCurrentDomain $goPayFacade
    ) {
        $this->em = $em;
        $this->orderFacade = $orderFacade;
        $this->orderMailFacade = $orderMailFacade;
        $this->goPayFacade = $goPayFacade;
    }

    public function run(): void
    {
        $now = new DateTime();
        $twentyOneDaysAgo = $now->sub(DateInterval::createFromDateString('21 days'));
        $orders = $this->orderFacade->getAllUnpaidGoPayOrders($twentyOneDaysAgo);

        $this->logger->debug('Downloading status updates for `' . count($orders) . '` orders.');

        foreach ($orders as $order) {
            $this->logger->debug('Downloading GoPay status for order with ID `' . $order->getId() . '`.');

            $oldOrderGoPayStatus = $order->getGoPayStatus();

            try {
                $goPayStatusResponse = $this->goPayFacade->getPaymentStatusResponse($order);
            } catch (GoPayPaymentDownloadException $e) {
                $this->logger->addError($e);

                continue;
            }

            $this->logger->info($goPayStatusResponse);

            if (array_key_exists('state', $goPayStatusResponse->json)) {
                $this->orderFacade->setGoPayStatusAndFik($order, $goPayStatusResponse);
            }

            if ($oldOrderGoPayStatus !== $order->getGoPayStatus()) {
                $this->logger->info(
                    sprintf(
                        'Order with id `%d` changed GoPay status from `%s` to `%s`.',
                        $order->getId(),
                        $oldOrderGoPayStatus,
                        $order->getGoPayStatus()
                    )
                );
            }

            $this->logger->info(sprintf('Order with id `%d` now has GoPay status: `%s`.', $order->getId(), $order->getGoPayStatus()));

            if ($oldOrderGoPayStatus !== $order->getGoPayStatus() && $order->getGoPayStatus() === PaymentStatus::PAID) {
                if ($order->getStatus()->getMailTemplateName() !== null) {
                    $this->logger->info('Sending order e-mail.');
                    $this->orderMailFacade->sendEmail($order);
                }
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
