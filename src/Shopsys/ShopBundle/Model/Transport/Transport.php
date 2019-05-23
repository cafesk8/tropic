<?php

namespace Shopsys\ShopBundle\Model\Transport;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Transport\Transport as BaseTransport;
use Shopsys\FrameworkBundle\Model\Transport\TransportData as BaseTransportData;

/**
 * @ORM\Table(name="transports")
 * @ORM\Entity
 */
class Transport extends BaseTransport
{
    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    protected $balikobot;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $balikobotShipper;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $balikobotShipperService;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    protected $pickupPlace;

    /**
     * @param \Shopsys\ShopBundle\Model\Transport\TransportData $transportData
     */
    public function __construct(BaseTransportData $transportData)
    {
        parent::__construct($transportData);
        $this->balikobot = $transportData->balikobot;
        $this->balikobotShipper = $transportData->balikobotShipper;
        $this->balikobotShipperService = $transportData->balikobotShipperService;
        $this->pickupPlace = $transportData->pickupPlace;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Transport\TransportData $transportData
     */
    public function edit(BaseTransportData $transportData)
    {
        parent::edit($transportData);
        $this->balikobot = $transportData->balikobot;
        $this->balikobotShipper = $transportData->balikobotShipper;
        $this->balikobotShipperService = $transportData->balikobotShipperService;
        $this->pickupPlace = $transportData->pickupPlace;
    }

    /**
     * @return bool
     */
    public function isBalikobot(): bool
    {
        return $this->balikobot;
    }

    /**
     * @return string|null
     */
    public function getBalikobotShipper(): ?string
    {
        return $this->balikobotShipper;
    }

    /**
     * @return string|null
     */
    public function getBalikobotShipperService(): ?string
    {
        return $this->balikobotShipperService;
    }

    /**
     * @return bool
     */
    public function isPickupPlace(): bool
    {
        return $this->pickupPlace;
    }
}
