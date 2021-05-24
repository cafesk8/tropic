<?php

declare(strict_types=1);

namespace App\Model\Payment;

use App\Model\GoPay\PaymentMethod\GoPayPaymentMethod;
use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Payment\Payment as BasePayment;
use Shopsys\FrameworkBundle\Model\Payment\PaymentData as BasePaymentData;

/**
 * @ORM\Table(name="payments")
 * @ORM\Entity
 * @property \App\Model\Transport\Transport[]|\Doctrine\Common\Collections\Collection $transports
 * @method addTransport(\App\Model\Transport\Transport $transport)
 * @method setTransports(\App\Model\Transport\Transport[] $transports)
 * @method removeTransport(\App\Model\Transport\Transport $transport)
 * @method \App\Model\Transport\Transport[] getTransports()
 * @method setTranslations(\App\Model\Payment\PaymentData $paymentData)
 * @property \App\Model\Payment\PaymentDomain[]|\Doctrine\Common\Collections\Collection $domains
 * @method \App\Model\Payment\PaymentDomain getPaymentDomain(int $domainId)
 */
class Payment extends BasePayment
{
    public const TYPE_BASIC = 'basic';
    public const TYPE_GOPAY = 'goPay';
    public const TYPE_PAY_PAL = 'payPal';
    public const TYPE_MALL = 'mall';
    public const TYPE_COFIDIS = 'cofidis';

    public const EXT_ID_ON_DELIVERY = 'Dobírkou';
    public const EXT_ID_BANK_TRANSFER = 'Příkazem';
    public const EXT_ID_CARD = 'Kartou';
    public const EXT_ID_COFIDIS = 'Cofidis';
    public const EXT_ID_CASH = 'Hotově';

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $type;

    /**
     * @var \App\Model\GoPay\PaymentMethod\GoPayPaymentMethod|null
     *
     * @ORM\ManyToOne(targetEntity="App\Model\GoPay\PaymentMethod\GoPayPaymentMethod")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $goPayPaymentMethod;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $externalId;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $cashOnDelivery;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $hiddenByGoPay;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $usableForGiftCertificates;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $activatesGiftCertificates;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private bool $waitForPayment;

    /**
     * @param \App\Model\Payment\PaymentData $paymentData
     */
    public function __construct(BasePaymentData $paymentData)
    {
        parent::__construct($paymentData);

        $this->fillCommonProperties($paymentData);
        $this->hiddenByGoPay = $paymentData->hiddenByGoPay;
    }

    /**
     * @param \App\Model\Payment\PaymentData $paymentData
     */
    public function edit(BasePaymentData $paymentData)
    {
        parent::edit($paymentData);

        $this->fillCommonProperties($paymentData);
    }

    /**
     * @param \App\Model\Payment\PaymentData $paymentData
     */
    private function fillCommonProperties(PaymentData $paymentData): void
    {
        $this->type = $paymentData->type;
        $this->setGoPayPaymentMethod($paymentData);
        $this->externalId = $paymentData->externalId;
        $this->cashOnDelivery = $paymentData->cashOnDelivery;
        $this->usableForGiftCertificates = $paymentData->usableForGiftCertificates;
        $this->activatesGiftCertificates = $this->type !== self::TYPE_GOPAY ? false : $paymentData->activatesGiftCertificates;
        $this->waitForPayment = $paymentData->waitForPayment;
    }

    /**
     * @return bool
     */
    public function isGoPay(): bool
    {
        return $this->type === self::TYPE_GOPAY;
    }

    /**
     * @return bool
     */
    public function isPayPal(): bool
    {
        return $this->type === self::TYPE_PAY_PAL;
    }

    /**
     * @return bool
     */
    public function isCofidis(): bool
    {
        return $this->type === self::TYPE_COFIDIS;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return \App\Model\GoPay\PaymentMethod\GoPayPaymentMethod|null
     */
    public function getGoPayPaymentMethod(): ?GoPayPaymentMethod
    {
        return $this->goPayPaymentMethod;
    }

    public function hide(): void
    {
        $this->hidden = true;
    }

    public function unHide(): void
    {
        $this->hidden = false;
    }

    /**
     * @param \App\Model\Payment\PaymentData $paymentData
     */
    private function setGoPayPaymentMethod(BasePaymentData $paymentData): void
    {
        $this->goPayPaymentMethod = null;

        if ($this->type === self::TYPE_GOPAY) {
            $this->goPayPaymentMethod = $paymentData->goPayPaymentMethod;
        }
    }

    /**
     * @return string|null
     */
    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    /**
     * @return bool
     */
    public function isCashOnDelivery(): bool
    {
        return $this->cashOnDelivery;
    }

    /**
     * @return bool
     */
    public function isHiddenByGoPay(): bool
    {
        return $this->hiddenByGoPay;
    }

    public function hideByGoPay(): void
    {
        $this->hiddenByGoPay = true;
    }

    public function unHideByGoPay(): void
    {
        $this->hiddenByGoPay = false;
    }

    /**
     * @return bool
     */
    public function isUsableForGiftCertificates(): bool
    {
        return $this->usableForGiftCertificates;
    }

    /**
     * @return bool
     */
    public function activatesGiftCertificates(): bool
    {
        return $this->activatesGiftCertificates;
    }

    /**
     * @return bool
     */
    public function waitsForPayment(): bool
    {
        return $this->waitForPayment;
    }

    /**
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Component\Money\Money
     */
    public function getMinimumOrderPrice(int $domainId): Money
    {
        return $this->getPaymentDomain($domainId)->getMinimumOrderPrice();
    }

    /**
     * @param \App\Model\Payment\PaymentData $paymentData
     */
    protected function setDomains(BasePaymentData $paymentData): void
    {
        parent::setDomains($paymentData);

        foreach ($this->domains as $paymentDomain) {
            $domainId = $paymentDomain->getDomainId();
            $paymentDomain->setMinimumOrderPrice($paymentData->minimumOrderPrices[$domainId] ?? Money::zero());
        }
    }

    /**
     * @param \App\Model\Payment\PaymentData $paymentData
     */
    protected function createDomains(BasePaymentData $paymentData)
    {
        $domainIds = array_keys($paymentData->enabled);

        foreach ($domainIds as $domainId) {
            $paymentDomain = new PaymentDomain($this, $domainId, $paymentData->vatsIndexedByDomainId[$domainId]);
            $this->domains->add($paymentDomain);
        }

        $this->setDomains($paymentData);
    }
}
