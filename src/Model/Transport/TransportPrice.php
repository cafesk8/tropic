<?php

declare(strict_types=1);

namespace App\Model\Transport;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Transport\Transport as BaseTransport;
use Shopsys\FrameworkBundle\Model\Transport\TransportPrice as BaseTransportPrice;

/**
 * @ORM\Table(name="transport_prices")
 * @ORM\Entity
 * @property \App\Model\Transport\Transport $transport
 * @method \App\Model\Transport\Transport getTransport()
 */
class TransportPrice extends BaseTransportPrice
{
    /**
     * @ORM\Column(type="money", precision=20, scale=6, nullable=true)
     */
    private ?Money $actionPrice;

    /**
     * @ORM\Column(type="money", precision=20, scale=6, nullable=true)
     */
    private ?Money $minActionOrderPrice;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $actionDateFrom;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $actionDateTo;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    private bool $actionActive;

    /**
     * @ORM\Column(type="money", precision=20, scale=6, nullable=true)
     */
    private ?Money $minFreeOrderPrice;

    /**
     * @ORM\Column(type="money", precision=20, scale=6, nullable=true)
     */
    private ?Money $maxOrderPriceLimit;

    /**
     * @param \App\Model\Transport\Transport $transport
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $price
     * @param int $domainId
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $actionPrice
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $minActionOrderPrice
     * @param \DateTime|null $actionDateFrom
     * @param \DateTime|null $actionDateTo
     * @param bool $actionActive
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $minFreeOrderPrice
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $maxOrderPriceLimit
     */
    public function __construct(
        BaseTransport $transport,
        Money $price,
        int $domainId,
        ?Money $actionPrice,
        ?Money $minActionOrderPrice,
        ?DateTime $actionDateFrom,
        ?DateTime $actionDateTo,
        bool $actionActive,
        ?Money $minFreeOrderPrice,
        ?Money $maxOrderPriceLimit
    ) {
        parent::__construct($transport, $price, $domainId);
        $this->actionPrice = $actionPrice;
        $this->minActionOrderPrice = $minActionOrderPrice;
        $this->actionDateFrom = $actionDateFrom;
        $this->actionDateTo = $actionDateTo;
        $this->actionActive = $actionActive;
        $this->minFreeOrderPrice = $minFreeOrderPrice;
        $this->maxOrderPriceLimit = $maxOrderPriceLimit;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    public function getActionPrice(): ?Money
    {
        return $this->actionPrice;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $actionPrice
     */
    public function setActionPrice(?Money $actionPrice): void
    {
        $this->actionPrice = $actionPrice;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    public function getMinActionOrderPrice(): ?Money
    {
        return $this->minActionOrderPrice;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $minActionOrderPrice
     */
    public function setMinActionOrderPrice(?Money $minActionOrderPrice): void
    {
        $this->minActionOrderPrice = $minActionOrderPrice;
    }

    /**
     * @return \DateTime|null
     */
    public function getActionDateFrom(): ?DateTime
    {
        return $this->actionDateFrom;
    }

    /**
     * @param \DateTime|null $actionDateFrom
     */
    public function setActionDateFrom(?DateTime $actionDateFrom): void
    {
        $this->actionDateFrom = $actionDateFrom;
    }

    /**
     * @return \DateTime|null
     */
    public function getActionDateTo(): ?DateTime
    {
        return $this->actionDateTo;
    }

    /**
     * @param \DateTime|null $actionDateTo
     */
    public function setActionDateTo(?DateTime $actionDateTo): void
    {
        $this->actionDateTo = $actionDateTo;
    }

    /**
     * @return bool
     */
    public function isActionActive(): bool
    {
        return $this->actionActive;
    }

    /**
     * @param bool $actionActive
     */
    public function setActionActive(bool $actionActive): void
    {
        $this->actionActive = $actionActive;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    public function getMinFreeOrderPrice(): ?\Shopsys\FrameworkBundle\Component\Money\Money
    {
        return $this->minFreeOrderPrice;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    public function getMaxOrderPriceLimit(): ?Money
    {
        return $this->maxOrderPriceLimit;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $minFreeOrderPrice
     */
    public function setMinFreeOrderPrice(?Money $minFreeOrderPrice): void
    {
        $this->minFreeOrderPrice = $minFreeOrderPrice;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $maxOrderPriceLimit
     */
    public function setMaxOrderPriceLimit(?Money $maxOrderPriceLimit): void
    {
        $this->maxOrderPriceLimit = $maxOrderPriceLimit;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $productsPrice
     * @return bool
     */
    public function canUseActionPrice(Price $productsPrice): bool
    {
        if (!$this->actionActive) {
            return false;
        }

        if ($this->actionPrice === null || $this->actionPrice->isGreaterThan($this->price)) {
            return false;
        }

        if ($this->minActionOrderPrice !== null && $this->minActionOrderPrice->isGreaterThan($productsPrice->getPriceWithVat())) {
            return false;
        }

        if ($this->actionDateFrom !== null && $this->actionDateFrom->getTimestamp() > time()) {
            return false;
        }

        if ($this->actionDateTo !== null && $this->actionDateTo->getTimestamp() + 86400 < time()) {
            return false;
        }

        return true;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $productsPrice
     * @return bool
     */
    public function isFree(Price $productsPrice): bool
    {
        if ($this->minFreeOrderPrice === null || $this->minFreeOrderPrice->isGreaterThan($productsPrice->getPriceWithVat())) {
            return false;
        }

        return true;
    }
}
