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
    private ?Money $minOrderPrice;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $actionDateFrom;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $actionDateTo;

    /**
     * @param \App\Model\Transport\Transport $transport
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $price
     * @param int $domainId
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $actionPrice
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $minOrderPrice
     * @param \DateTime|null $actionDateFrom
     * @param \DateTime|null $actionDateTo
     */
    public function __construct(
        BaseTransport $transport,
        Money $price,
        int $domainId,
        ?Money $actionPrice,
        ?Money $minOrderPrice,
        ?DateTime $actionDateFrom,
        ?DateTime $actionDateTo
    ) {
        parent::__construct($transport, $price, $domainId);
        $this->actionPrice = $actionPrice;
        $this->minOrderPrice = $minOrderPrice;
        $this->actionDateFrom = $actionDateFrom;
        $this->actionDateTo = $actionDateTo;
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
    public function getMinOrderPrice(): ?Money
    {
        return $this->minOrderPrice;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $minOrderPrice
     */
    public function setMinOrderPrice(?Money $minOrderPrice): void
    {
        $this->minOrderPrice = $minOrderPrice;
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
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $productsPrice
     * @return bool
     */
    public function canUseActionPrice(Price $productsPrice): bool
    {
        if ($this->actionPrice === null || $this->actionPrice->isGreaterThan($this->price)) {
            return false;
        }

        if ($this->minOrderPrice !== null && $this->minOrderPrice->isGreaterThan($productsPrice->getPriceWithVat())) {
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
}
