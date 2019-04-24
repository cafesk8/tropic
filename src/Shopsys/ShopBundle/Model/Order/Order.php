<?php

namespace Shopsys\ShopBundle\Model\Order;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Customer\User;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItemFactoryInterface;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItemPriceCalculation;
use Shopsys\FrameworkBundle\Model\Order\Order as BaseOrder;
use Shopsys\FrameworkBundle\Model\Order\OrderData as BaseOrderData;
use Shopsys\FrameworkBundle\Model\Order\OrderEditResult;
use Shopsys\FrameworkBundle\Model\Order\OrderPriceCalculation;

/**
 * @ORM\Table(name="orders")
 * @ORM\Entity
 */
class Order extends BaseOrder
{
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $goPayId;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=30, nullable=true)
     */
    private $goPayStatus;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=39, nullable=true)
     */
    private $goPayFik;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $payPalId;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $payPalStatus;

    /**
     * @param \Shopsys\ShopBundle\Model\Order\OrderData $orderData
     * @param string $orderNumber
     * @param string $urlHash
     * @param \Shopsys\FrameworkBundle\Model\Customer\User|null $user
     */
    public function __construct(
        BaseOrderData $orderData,
        string $orderNumber,
        string $urlHash,
        ?User $user = null
    ) {
        parent::__construct($orderData, $orderNumber, $urlHash, $user);

        $this->goPayId = $orderData->goPayId;
        $this->goPayStatus = $orderData->goPayStatus;
        $this->payPalId = $orderData->payPalId;
        $this->payPalStatus = $orderData->payPalStatus;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\OrderData $orderData
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\OrderItemPriceCalculation $orderItemPriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\OrderItemFactoryInterface $orderItemFactory
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderPriceCalculation $orderPriceCalculation
     * @return \Shopsys\FrameworkBundle\Model\Order\OrderEditResult
     */
    public function edit(
        BaseOrderData $orderData,
        OrderItemPriceCalculation $orderItemPriceCalculation,
        OrderItemFactoryInterface $orderItemFactory,
        OrderPriceCalculation $orderPriceCalculation
    ): OrderEditResult {
        $orderEditResult = parent::edit($orderData, $orderItemPriceCalculation, $orderItemFactory, $orderPriceCalculation);

        $this->goPayId = $orderData->goPayId;
        $this->goPayStatus = $orderData->goPayStatus;
        $this->payPalId = $orderData->payPalId;
        $this->payPalStatus = $orderData->payPalStatus;

        return $orderEditResult;
    }

    /**
     * @return string|null
     */
    public function getGoPayId(): ?string
    {
        return $this->goPayId;
    }

    /**
     * @param string|null $goPayId
     */
    public function setGoPayId(?string $goPayId): void
    {
        $this->goPayId = $goPayId;
    }

    /**
     * @return string|null
     */
    public function getGoPayStatus(): ?string
    {
        return $this->goPayStatus;
    }

    /**
     * @param string $goPayStatus
     */
    public function setGoPayStatus(string $goPayStatus): void
    {
        $this->goPayStatus = $goPayStatus;
    }

    /**
     * @return string|null
     */
    public function getGoPayFik(): ?string
    {
        return $this->goPayFik;
    }

    /**
     * @param string|null $goPayFik
     */
    public function setGoPayFik(?string $goPayFik)
    {
        $this->goPayFik = $goPayFik;
    }

    /**
     * @return string|null
     */
    public function getPayPalId(): ?string
    {
        return $this->payPalId;
    }

    /**
     * @param string|null $payPalId
     */
    public function setPayPalId(?string $payPalId): void
    {
        $this->payPalId = $payPalId;
    }

    /**
     * @param string|null $payPalStatus
     */
    public function setPayPalStatus(?string $payPalStatus): void
    {
        $this->payPalStatus = $payPalStatus;
    }

    /**
     * @return string|null
     */
    public function getPayPalStatus(): ?string
    {
        return $this->payPalStatus;
    }
}
