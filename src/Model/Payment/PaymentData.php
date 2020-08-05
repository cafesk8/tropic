<?php

declare(strict_types=1);

namespace App\Model\Payment;

use Shopsys\FrameworkBundle\Model\Payment\PaymentData as BasePaymentData;

/**
 * @property \App\Model\Transport\Transport[] $transports
 * @property \App\Model\Pricing\Vat\Vat[] $vatsIndexedByDomainId
 */
class PaymentData extends BasePaymentData
{
    /**
     * @var string
     */
    public $type;

    /**
     * @var \App\Model\GoPay\PaymentMethod\GoPayPaymentMethod|null
     */
    public $goPayPaymentMethod;

    /**
     * @var string|null
     */
    public $externalId;

    /**
     * @var bool
     */
    public $cashOnDelivery;

    /**
     * @var bool
     */
    public $hiddenByGoPay;

    /**
     * @var bool
     */
    public $usableForGiftCertificates;

    /**
     * @var bool
     */
    public $activatesGiftCertificates;

    /**
     * @var bool
     */
    public bool $waitForPayment;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Money\Money[]
     */
    public array $minimumOrderPrices;

    public function __construct()
    {
        parent::__construct();

        $this->type = Payment::TYPE_BASIC;
        $this->minimumOrderPrices = [];
    }
}
