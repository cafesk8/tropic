<?php

declare(strict_types=1);

namespace App\Model\Order\Discount;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Component\Money\Money;

/**
 * @ORM\Entity
 * @ORM\Table(name="order_discount_levels")
 */
class OrderDiscountLevel
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(type="boolean")
     *
     * @var bool
     */
    private $enabled;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Money\Money
     *
     * @ORM\Column(type="money", precision=20, scale=6)
     */
    private $priceLevelWithVat;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    private $domainId;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $discountPercent;

    /**
     * @param \App\Model\Order\Discount\OrderDiscountLevelData $orderDiscountLevelData
     */
    public function __construct(OrderDiscountLevelData $orderDiscountLevelData)
    {
        $this->domainId = $orderDiscountLevelData->domainId;
        $this->fillCommonFields($orderDiscountLevelData);
    }

    /**
     * @param \App\Model\Order\Discount\OrderDiscountLevelData $orderDiscountLevelData
     */
    public function edit(OrderDiscountLevelData $orderDiscountLevelData)
    {
        $this->fillCommonFields($orderDiscountLevelData);
    }

    /**
     * @param \App\Model\Order\Discount\OrderDiscountLevelData $orderDiscountLevelData
     */
    private function fillCommonFields(OrderDiscountLevelData $orderDiscountLevelData): void
    {
        $this->discountPercent = $orderDiscountLevelData->discountPercent;
        $this->enabled = $orderDiscountLevelData->enabled;
        $this->priceLevelWithVat = $orderDiscountLevelData->priceLevelWithVat;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Money\Money
     */
    public function getPriceLevelWithVat(): Money
    {
        return $this->priceLevelWithVat;
    }

    /**
     * @return int
     */
    public function getDomainId(): int
    {
        return $this->domainId;
    }

    /**
     * @return int
     */
    public function getDiscountPercent(): int
    {
        return $this->discountPercent;
    }
}
