<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\PayPal;

use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use Shopsys\ShopBundle\Model\Order\Order;

class PayPalClient
{
    /**
     * @var \PayPal\Rest\ApiContext
     */
    private $apiContext;

    /**
     * @param string $payPalClientId
     * @param string $payPalClientSecret
     */
    public function __construct(string $payPalClientId, string $payPalClientSecret)
    {
        $oAuthTokenCredential = new OAuthTokenCredential($payPalClientId, $payPalClientSecret);
        $this->apiContext = new ApiContext($oAuthTokenCredential);
    }

    /**
     * @param \PayPal\Api\Payment $payment
     */
    public function sendPayment(Payment $payment): void
    {
        $payment->create($this->apiContext);
    }

    /**
     * @param string $paymentId
     * @return string
     */
    public function getPaymentStatus(string $paymentId): string
    {
        $payment = Payment::get($paymentId, $this->apiContext);

        return $payment->getState();
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @return string
     */
    public function executePayment(Order $order): string
    {
        $payment = Payment::get($order->getPayPalId(), $this->apiContext);

        $paymentExecution = (new PaymentExecution())
            ->setPayerId($payment->getPayer()->getPayerInfo()->getPayerId());

        return $payment->execute($paymentExecution, $this->apiContext)->getState();
    }
}
