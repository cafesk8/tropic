<?php

declare(strict_types=1);

namespace App\Model\Payment;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Image\ImageFacade;
use Shopsys\FrameworkBundle\Model\Payment\Payment as BasePayment;
use Shopsys\FrameworkBundle\Model\Payment\PaymentData as BasePaymentData;
use Shopsys\FrameworkBundle\Model\Payment\PaymentDataFactory as BasePaymentDataFactory;
use Shopsys\FrameworkBundle\Model\Payment\PaymentFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Vat\VatFacade;

class PaymentDataFactory extends BasePaymentDataFactory
{
    /**
     * @param \App\Model\Payment\PaymentFacade $paymentFacade
     * @param \App\Model\Pricing\Vat\VatFacade $vatFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Component\Image\ImageFacade $imageFacade
     */
    public function __construct(
        PaymentFacade $paymentFacade,
        VatFacade $vatFacade,
        Domain $domain,
        ImageFacade $imageFacade
    ) {
        parent::__construct($paymentFacade, $vatFacade, $domain, $imageFacade);
    }

    /**
     * @return \App\Model\Payment\PaymentData
     */
    public function create(): BasePaymentData
    {
        $paymentData = new PaymentData();
        $this->fillNew($paymentData);

        return $paymentData;
    }

    /**
     * @param \App\Model\Payment\Payment $payment
     * @return \App\Model\Payment\PaymentData
     */
    public function createFromPayment(BasePayment $payment): BasePaymentData
    {
        $paymentData = new PaymentData();
        $this->fillFromPayment($paymentData, $payment);

        $paymentData->type = $payment->getType();
        $paymentData->goPayPaymentMethod = $payment->getGoPayPaymentMethod();
        $paymentData->externalId = $payment->getExternalId();
        $paymentData->cashOnDelivery = $payment->isCashOnDelivery();
        $paymentData->hiddenByGoPay = $payment->isHiddenByGoPay();
        $paymentData->usableForGiftCertificates = $payment->isUsableForGiftCertificates();
        $paymentData->activatesGiftCertificates = $payment->activatesGiftCertificates();
        $paymentData->waitForPayment = $payment->waitsForPayment();

        foreach ($this->domain->getAllIds() as $domainId) {
            $paymentData->minimumOrderPrices[$domainId] = $payment->getMinimumOrderPrice($domainId);
        }
        return $paymentData;
    }

    /**
     * @param \App\Model\Payment\PaymentData $paymentData
     */
    protected function fillNew(BasePaymentData $paymentData): void
    {
        parent::fillNew($paymentData);

        $paymentData->cashOnDelivery = false;
        $paymentData->hiddenByGoPay = false;
        $paymentData->usableForGiftCertificates = false;
        $paymentData->activatesGiftCertificates = false;
        $paymentData->waitForPayment = false;

        foreach ($this->domain->getAllIds() as $domainId) {
            $paymentData->minimumOrderPrices[$domainId] = Money::zero();
        }
    }
}
