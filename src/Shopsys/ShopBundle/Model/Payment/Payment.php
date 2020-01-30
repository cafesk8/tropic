<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Payment;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Payment\Payment as BasePayment;
use Shopsys\FrameworkBundle\Model\Payment\PaymentData as BasePaymentData;
use Shopsys\ShopBundle\Model\GoPay\PaymentMethod\GoPayPaymentMethod;

/**
 * @ORM\Table(name="payments")
 * @ORM\Entity
 */
class Payment extends BasePayment
{
    public const TYPE_BASIC = 'basic';
    public const TYPE_GOPAY = 'goPay';
    public const TYPE_PAY_PAL = 'payPal';
    public const TYPE_MALL = 'mall';

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $type;

    /**
     * @var \Shopsys\ShopBundle\Model\GoPay\PaymentMethod\GoPayPaymentMethod|null
     *
     * @ORM\ManyToOne(targetEntity="Shopsys\ShopBundle\Model\GoPay\PaymentMethod\GoPayPaymentMethod")
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
     * @param \Shopsys\ShopBundle\Model\Payment\PaymentData $paymentData
     */
    public function __construct(BasePaymentData $paymentData)
    {
        parent::__construct($paymentData);

        $this->type = $paymentData->type;
        $this->setGoPayPaymentMethod($paymentData);
        $this->externalId = $paymentData->externalId;
        $this->cashOnDelivery = $paymentData->cashOnDelivery;
        $this->hiddenByGoPay = $paymentData->hiddenByGoPay;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Payment\PaymentData $paymentData
     */
    public function edit(BasePaymentData $paymentData)
    {
        parent::edit($paymentData);

        $this->type = $paymentData->type;
        $this->setGoPayPaymentMethod($paymentData);
        $this->externalId = $paymentData->externalId;
        $this->cashOnDelivery = $paymentData->cashOnDelivery;
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
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\GoPay\PaymentMethod\GoPayPaymentMethod|null
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
     * @param \Shopsys\ShopBundle\Model\Payment\PaymentData $paymentData
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
}
