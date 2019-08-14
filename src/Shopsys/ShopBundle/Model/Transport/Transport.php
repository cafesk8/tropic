<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Transport;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Country\Country;
use Shopsys\FrameworkBundle\Model\Transport\Transport as BaseTransport;
use Shopsys\FrameworkBundle\Model\Transport\TransportData as BaseTransportData;
use Shopsys\ShopBundle\Form\Admin\TransportFormTypeExtension;

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
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    protected $initialDownload;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    protected $chooseStore;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $mallType;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection|\Shopsys\FrameworkBundle\Model\Country\Country[]
     *
     * @ORM\ManyToMany(targetEntity="Shopsys\FrameworkBundle\Model\Country\Country")
     * @ORM\JoinTable(name="transport_countries")
     */
    protected $countries;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    private $deliveryDays;

    /**
     * @param \Shopsys\ShopBundle\Model\Transport\TransportData $transportData
     */
    public function __construct(BaseTransportData $transportData)
    {
        parent::__construct($transportData);
        $this->balikobot = $transportData->personalTakeType === TransportFormTypeExtension::PERSONAL_TAKE_TYPE_BALIKOBOT;
        $this->balikobotShipper = $transportData->balikobotShipper;
        $this->balikobotShipperService = $transportData->balikobotShipperService;
        $this->pickupPlace = $transportData->pickupPlace;
        $this->initialDownload = $transportData->initialDownload;
        $this->chooseStore = $transportData->personalTakeType === TransportFormTypeExtension::PERSONAL_TAKE_TYPE_STORE;
        $this->countries = $transportData->countries;
        $this->mallType = $transportData->mallType;
        $this->deliveryDays = $transportData->deliveryDays;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Transport\TransportData $transportData
     */
    public function edit(BaseTransportData $transportData): void
    {
        parent::edit($transportData);
        $this->balikobot = $transportData->personalTakeType === TransportFormTypeExtension::PERSONAL_TAKE_TYPE_BALIKOBOT;
        $this->balikobotShipper = $transportData->balikobotShipper;
        $this->balikobotShipperService = $transportData->balikobotShipperService;
        $this->pickupPlace = $transportData->pickupPlace;
        $this->initialDownload = $transportData->initialDownload;
        $this->chooseStore = $transportData->personalTakeType === TransportFormTypeExtension::PERSONAL_TAKE_TYPE_STORE;
        $this->countries = $transportData->countries;
        $this->mallType = $transportData->mallType;
        $this->deliveryDays = $transportData->deliveryDays;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Transport\TransportData $transportData
     * @return bool
     */
    public function isBalikobotChanged(BaseTransportData $transportData): bool
    {
        if ($this->balikobotShipper !== $transportData->balikobotShipper) {
            return true;
        }
        if ($this->balikobotShipperService !== $transportData->balikobotShipperService) {
            return true;
        }

        return false;
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

    /**
     * @return bool
     */
    public function isInitialDownload(): bool
    {
        return $this->initialDownload;
    }

    public function setAsDownloaded(): void
    {
        $this->initialDownload = false;
    }

    /**
     * @return bool
     */
    public function isChooseStore(): bool
    {
        return $this->chooseStore;
    }

    /**
     * @return bool
     */
    public function isPickupPlaceType(): bool
    {
        return $this->isPickupPlace() || $this->isChooseStore();
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Country\Country[]
     */
    public function getCountries()
    {
        return $this->countries->toArray();
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Country\Country|null $country
     * @return bool
     */
    public function hasCountry(?Country $country): bool
    {
        return $this->countries->contains($country);
    }

    /**
     * @return string|null
     */
    public function getMallType(): ?string
    {
        return $this->mallType;
    }

    /**
     * @return int
     */
    public function getDeliveryDays(): int
    {
        return $this->deliveryDays;
    }
}
