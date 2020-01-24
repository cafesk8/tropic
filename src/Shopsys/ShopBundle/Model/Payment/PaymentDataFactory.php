<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Payment;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Payment\Payment as BasePayment;
use Shopsys\FrameworkBundle\Model\Payment\PaymentData as BasePaymentData;
use Shopsys\FrameworkBundle\Model\Payment\PaymentDataFactory as BasePaymentDataFactory;
use Shopsys\FrameworkBundle\Model\Payment\PaymentFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Vat\VatFacade;

class PaymentDataFactory extends BasePaymentDataFactory
{
    /**
     * @param \Shopsys\ShopBundle\Model\Payment\PaymentFacade $paymentFacade
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Vat\VatFacade $vatFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(
        PaymentFacade $paymentFacade,
        VatFacade $vatFacade,
        Domain $domain
    ) {
        parent::__construct($paymentFacade, $vatFacade, $domain);
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Payment\PaymentData
     */
    public function create(): BasePaymentData
    {
        $paymentData = new PaymentData();
        $this->fillNew($paymentData);

        return $paymentData;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Payment\Payment $payment
     * @return \Shopsys\ShopBundle\Model\Payment\PaymentData
     */
    public function createFromPayment(BasePayment $payment): BasePaymentData
    {
        $paymentData = new PaymentData();
        $this->fillFromPayment($paymentData, $payment);

        $paymentData->type = $payment->getType();
        $paymentData->goPayPaymentMethod = $payment->getGoPayPaymentMethod();
        $paymentData->externalId = $payment->getExternalId();
        $paymentData->cashOnDelivery = $payment->isCashOnDelivery();

        return $paymentData;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Payment\PaymentData $paymentData
     */
    protected function fillNew(BasePaymentData $paymentData): void
    {
        parent::fillNew($paymentData);

        $paymentData->cashOnDelivery = false;
    }
}
