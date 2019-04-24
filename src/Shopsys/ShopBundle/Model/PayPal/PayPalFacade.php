<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\PayPal;

use Doctrine\ORM\EntityManagerInterface;
use PayPal\Api\Address;
use PayPal\Api\Amount;
use PayPal\Api\Payer;
use PayPal\Api\PayerInfo;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Router\DomainRouterFactory;
use Shopsys\ShopBundle\Model\Order\Order;
use Shopsys\ShopBundle\Model\Order\OrderFacade;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PayPalFacade
{
    public const PAYMENT_APPROVED = 'approved';

    /**
     * @var \Shopsys\ShopBundle\Model\PayPal\PayPalClient
     */
    private $payPalClient;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Router\DomainRouterFactory
     */
    private $domainRouterFactory;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @var \Shopsys\ShopBundle\Model\Order\OrderFacade
     */
    private $orderFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * PayPalFacade constructor.
     * @param \Shopsys\ShopBundle\Model\PayPal\PayPalClient $payPalClient
     * @param \Shopsys\FrameworkBundle\Component\Router\DomainRouterFactory $domainRouterFactory
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\ShopBundle\Model\Order\OrderFacade $orderFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(
        PayPalClient $payPalClient,
        DomainRouterFactory $domainRouterFactory,
        EntityManagerInterface $em,
        OrderFacade $orderFacade,
        Domain $domain
    ) {
        $this->payPalClient = $payPalClient;
        $this->domainRouterFactory = $domainRouterFactory;
        $this->em = $em;
        $this->orderFacade = $orderFacade;
        $this->domain = $domain;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @return \PayPal\Api\Payment
     */
    public function sendPayment(Order $order): Payment
    {
        $payment = $this->createPayment($order);

        $this->payPalClient->sendPayment($payment);

        $order->setPayPalId($payment->getId());
        $this->em->flush();

        return $payment;
    }

    /**
     * @param \PayPal\Api\PayerInfo $payerInfo
     * @return \PayPal\Api\Payer
     */
    private function createPayer(PayerInfo $payerInfo): Payer
    {
        return (new Payer())
            ->setPaymentMethod('paypal')
            ->setPayerInfo($payerInfo);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @return \PayPal\Api\Address
     */
    private function createBillingAddress(Order $order): Address
    {
        return (new Address())
            ->setLine1($order->getStreet())
            ->setCity($order->getCity())
            ->setPostalCode($order->getPostcode())
            ->setCountryCode($order->getCountry()->getCode())
            ->setPhone($order->getTelephone());
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @return \PayPal\Api\PayerInfo
     */
    private function createPayerInfo(Order $order): PayerInfo
    {
        return (new PayerInfo())
            ->setFirstName($order->getFirstName())
            ->setLastName($order->getLastName())
            ->setBillingAddress($this->createBillingAddress($order))
            ->setEmail($order->getEmail());
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @return \PayPal\Api\RedirectUrls
     */
    private function createRedirectUrls(Order $order): RedirectUrls
    {
        $orderPaidUrl = $this->domainRouterFactory->getRouter($this->domain->getId())->generate(
            'front_order_paid',
            ['urlHash' => $order->getUrlHash()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $orderNotPaidUrl = $this->domainRouterFactory->getRouter($this->domain->getId())->generate(
            'front_order_not_paid',
            ['urlHash' => $order->getUrlHash()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return (new RedirectUrls())
            ->setReturnUrl($orderPaidUrl)
            ->setCancelUrl($orderNotPaidUrl);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @return \PayPal\Api\Payment
     */
    private function createPayment(Order $order): Payment
    {
        $payerInfo = $this->createPayerInfo($order);
        $payer = $this->createPayer($payerInfo);
        $amount = $this->createAmount($order);
        $transaction = $this->createTransaction($order, $amount);
        $redirectUrls = $this->createRedirectUrls($order);

        return (new Payment())
            ->setIntent('sale')
            ->setPayer($payer)
            ->setTransactions([$transaction])
            ->setRedirectUrls($redirectUrls);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @param \PayPal\Api\Amount $amount
     * @return \PayPal\Api\Transaction
     */
    private function createTransaction(Order $order, Amount $amount): Transaction
    {
        $orderNotifyPayPalUrl = $this->domainRouterFactory->getRouter($this->domain->getId())->generate(
            'front_order_paypal_status_notify',
            ['orderId' => $order->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return (new Transaction())
            ->setAmount($amount)
            ->setPurchaseOrder($order->getNumber())
            ->setNotifyUrl($orderNotifyPayPalUrl);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @return \PayPal\Api\Amount
     */
    private function createAmount(Order $order): Amount
    {
        return (new Amount())
            ->setTotal($order->getTotalPriceWithVat()->getAmount())
            ->setCurrency($order->getCurrency()->getCode());
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @return bool
     */
    public function isOrderPaid(Order $order): bool
    {
        return $order->getPayPalStatus() === self::PAYMENT_APPROVED;
    }

    /**
     * @param string $paymentId
     * @return string
     */
    public function getPaymentStatus(string $paymentId): string
    {
        return $this->payPalClient->getPaymentStatus($paymentId);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     */
    public function updateOrderPayPalStatus(Order $order): void
    {
        if ($order->getPayPalStatus() === self::PAYMENT_APPROVED) {
            return;
        }

        $payPalStatus = $this->getPaymentStatus($order->getPayPalId());
        $this->orderFacade->setPayPalStatus($order, $payPalStatus);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     */
    public function executePayment(Order $order): void
    {
        $payPalStatus = $this->payPalClient->executePayment($order);
        $this->orderFacade->setPayPalStatus($order, $payPalStatus);
    }
}
