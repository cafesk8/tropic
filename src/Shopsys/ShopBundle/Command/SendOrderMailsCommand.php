<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Command;

use Shopsys\FrameworkBundle\Model\Mail\Exception\MailException;
use Shopsys\FrameworkBundle\Model\Order\Mail\OrderMailFacade;
use Shopsys\ShopBundle\Component\Domain\DomainHelper;
use Shopsys\ShopBundle\Model\Order\OrderFacade;
use Shopsys\ShopBundle\Model\Order\Status\OrderStatus;
use Shopsys\ShopBundle\Model\Order\Status\OrderStatusFacade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SendOrderMailsCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'shopsys:order-mails:send';

    /**
     * @var \Shopsys\ShopBundle\Model\Order\OrderFacade
     */
    protected $orderFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Order\Status\OrderStatusFacade
     */
    protected $orderStatusFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Order\Mail\OrderMailFacade
     */
    protected $orderMailFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Order\OrderFacade $orderFacade
     * @param \Shopsys\ShopBundle\Model\Order\Status\OrderStatusFacade $orderStatusFacade
     * @param \Shopsys\FrameworkBundle\Model\Order\Mail\OrderMailFacade $orderMailFacade
     */
    public function __construct(OrderFacade $orderFacade, OrderStatusFacade $orderStatusFacade, OrderMailFacade $orderMailFacade)
    {
        $this->orderFacade = $orderFacade;
        $this->orderStatusFacade = $orderStatusFacade;
        $this->orderMailFacade = $orderMailFacade;

        parent::__construct();
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $symfonyStyleIo = new SymfonyStyle($input, $output);
        $newOrderStatus = $this->orderStatusFacade->getByType(OrderStatus::TYPE_NEW);
        $from = new \DateTime('2019-11-02 6:45');
        $to = new \DateTime('2019-11-04 11:57');

        $ordersCreatedInRange = $this->orderFacade->getAllCreatedInRange($from, $to);
        foreach ($ordersCreatedInRange as $order) {
            $currentOrderStatus = $order->getStatus();
            try {
                if ($currentOrderStatus === $newOrderStatus) {
                    $this->orderMailFacade->sendEmail($order);
                    $symfonyStyleIo->success(sprintf('Mail sent for order no. "%s" and status "%s"', $order->getNumber(), $newOrderStatus->getName(DomainHelper::CZECH_LOCALE)));
                } else {
                    $order->setStatus($newOrderStatus);
                    $this->orderMailFacade->sendEmail($order);
                    $symfonyStyleIo->success(sprintf('Mail sent for order no. "%s" and status "%s"', $order->getNumber(), $newOrderStatus->getName(DomainHelper::CZECH_LOCALE)));
                    $order->setStatus($currentOrderStatus);
                    $this->orderMailFacade->sendEmail($order);
                    $symfonyStyleIo->success(sprintf('Mail sent for order no. "%s" and status "%s"', $order->getNumber(), $currentOrderStatus->getName(DomainHelper::CZECH_LOCALE)));
                }
            } catch (MailException $mailException) {
                $symfonyStyleIo->warning(sprintf('Problem with sending mail for order no. %s: %s', $order->getNumber(), $mailException->getMessage()));
            }
        }

        $ordersUpdatedButNotCreatedInRange = $this->orderFacade->getAllUpdatedButNotCreatedSince($from);
        foreach ($ordersUpdatedButNotCreatedInRange as $order) {
            $currentOrderStatus = $order->getStatus();
            try {
                if ($currentOrderStatus !== $newOrderStatus) {
                    $this->orderMailFacade->sendEmail($order);
                    $symfonyStyleIo->success(sprintf('Mail sent for order no. "%s" and status "%s"', $order->getNumber(), $currentOrderStatus->getName(DomainHelper::CZECH_LOCALE)));
                }
            } catch (MailException $mailException) {
                $symfonyStyleIo->warning(sprintf('Problem with sending mail for order no. %s: %s', $order->getNumber(), $mailException->getMessage()));
            }
        }
    }
}
