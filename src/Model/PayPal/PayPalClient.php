<?php

declare(strict_types=1);

namespace App\Model\PayPal;

use App\Model\Order\Order;
use App\Model\PayPal\Exception\UnsupportedPayPalModeException;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;

class PayPalClient
{
    public const MODE_SANDBOX = 'SANDBOX';
    public const MODE_LIVE = 'LIVE';

    /**
     * @var \PayPal\Rest\ApiContext
     */
    private $apiContext;

    /**
     * @param string $payPalClientId
     * @param string $payPalClientSecret
     * @param string $payPalMode
     */
    public function __construct(string $payPalClientId, string $payPalClientSecret, string $payPalMode)
    {
        if (in_array($payPalMode, [self::MODE_SANDBOX, self::MODE_LIVE], true) === false) {
            throw new UnsupportedPayPalModeException();
        }

        $oAuthTokenCredential = new OAuthTokenCredential($payPalClientId, $payPalClientSecret);

        $apiContext = new ApiContext($oAuthTokenCredential);
        $apiContext->setConfig([
            'mode' => $payPalMode,
        ]);
        $this->apiContext = $apiContext;
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
     * @param \App\Model\Order\Order $order
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
