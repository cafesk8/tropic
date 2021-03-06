<?php

declare(strict_types=1);

namespace App\Model\Transport;

use App\Model\Transport\Exception\InvalidPersonalTakeTypeException;
use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Country\Country;
use Shopsys\FrameworkBundle\Model\Transport\Transport as BaseTransport;
use Shopsys\FrameworkBundle\Model\Transport\TransportData as BaseTransportData;

/**
 * @ORM\Table(name="transports")
 * @ORM\Entity
 * @property \App\Model\Payment\Payment[]|\Doctrine\Common\Collections\Collection $payments
 * @method setTranslations(\App\Model\Transport\TransportData $transportData)
 * @method setDomains(\App\Model\Transport\TransportData $transportData)
 * @method createDomains(\App\Model\Transport\TransportData $transportData)
 * @method addPayment(\App\Model\Payment\Payment $payment)
 * @method setPayments(\App\Model\Payment\Payment[] $payments)
 * @method removePayment(\App\Model\Payment\Payment $payment)
 * @method \App\Model\Payment\Payment[] getPayments()
 * @method \App\Model\Transport\TransportPrice[] getPrices()
 * @property \App\Model\Transport\TransportPrice[]|\Doctrine\Common\Collections\Collection $prices
 * @method addPrice(\App\Model\Transport\TransportPrice $transportPrice)
 * @method \App\Model\Transport\TransportPrice getPrice(int $domainId)
 */
class Transport extends BaseTransport
{
    public const TYPE_NONE = 'none';
    public const TYPE_PERSONAL_TAKE_BALIKOBOT = 'balikobot';
    public const TYPE_PERSONAL_TAKE_STORE = 'store';
    public const TYPE_EMAIL = 'e-mail';
    public const TYPE_ZASILKOVNA_CZ = 'zasilkovnaCZ';
    public const TYPE_ZASILKOVNA_SK = 'zasilkovnaSK';

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
     * @var \App\Model\Country\Country[]|\Doctrine\Common\Collections\Collection|array
     *
     * @ORM\ManyToMany(targetEntity="Shopsys\FrameworkBundle\Model\Country\Country")
     * @ORM\JoinTable(name="transport_countries")
     */
    protected $countries;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $externalId;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     */
    public $transportType;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $trackingUrlPattern;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $mergadoTransportType;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private bool $bulkyAllowed;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private bool $oversizedAllowed;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $zboziType;

    /**
     * @param \App\Model\Transport\TransportData $transportData
     */
    public function __construct(BaseTransportData $transportData)
    {
        parent::__construct($transportData);
        $this->fillCommonProperties($transportData);
    }

    /**
     * @param \App\Model\Transport\TransportData $transportData
     */
    public function edit(BaseTransportData $transportData): void
    {
        parent::edit($transportData);
        $this->fillCommonProperties($transportData);
    }

    /**
     * @param \App\Model\Transport\TransportData $transportData
     */
    private function fillCommonProperties(TransportData $transportData): void
    {
        $this->balikobot = $transportData->transportType === self::TYPE_PERSONAL_TAKE_BALIKOBOT;
        $this->balikobotShipper = $transportData->balikobotShipper;
        $this->balikobotShipperService = $transportData->balikobotShipperService;
        $this->pickupPlace = $transportData->pickupPlace;
        $this->initialDownload = $transportData->initialDownload;
        $this->chooseStore = $transportData->transportType === self::TYPE_PERSONAL_TAKE_STORE;
        $this->countries = $transportData->countries;
        $this->mallType = $transportData->mallType;
        $this->externalId = $transportData->externalId;
        $this->setTransportType($transportData->transportType);
        $this->trackingUrlPattern = $transportData->trackingUrlPattern;
        $this->mergadoTransportType = $transportData->mergadoTransportType;
        $this->bulkyAllowed = $transportData->bulkyAllowed;
        $this->oversizedAllowed = $transportData->oversizedAllowed;
        $this->zboziType = $transportData->zboziType;
    }

    /**
     * @param \App\Model\Transport\TransportData $transportData
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
     * @return bool
     */
    public function isPacketaType(): bool
    {
        return $this->transportType === self::TYPE_ZASILKOVNA_CZ || $this->transportType === self::TYPE_ZASILKOVNA_SK;
    }

    /**
     * @return \App\Model\Country\Country[]
     */
    public function getCountries(): array
    {
        return $this->countries->toArray();
    }

    /**
     * @param \App\Model\Country\Country|null $country
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
     * @return string|null
     */
    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    /**
     * @return string
     */
    public function getTransportType(): string
    {
        return $this->transportType;
    }

    /**
     * @param string $type
     */
    private function setTransportType(string $type): void
    {
        if (in_array($type, [
                self::TYPE_NONE,
                self::TYPE_PERSONAL_TAKE_BALIKOBOT,
                self::TYPE_PERSONAL_TAKE_STORE,
                self::TYPE_EMAIL,
                self::TYPE_ZASILKOVNA_CZ,
                self::TYPE_ZASILKOVNA_SK,
            ], true) === false) {
            throw new InvalidPersonalTakeTypeException('Invalid transport type `%s`', $type);
        }
        $this->transportType = $type;
    }

    /**
     * @return string|null
     */
    public function getTrackingUrlPattern(): ?string
    {
        return $this->trackingUrlPattern;
    }

    /**
     * @return string|null
     */
    public function getMergadoTransportType(): ?string
    {
        return $this->mergadoTransportType;
    }

    /**
     * @return bool
     */
    public function isBulkyAllowed(): bool
    {
        return $this->bulkyAllowed;
    }

    /**
     * @return bool
     */
    public function isOversizedAllowed(): bool
    {
        return $this->oversizedAllowed;
    }

    /**
     * @return string|null
     */
    public function getZboziType(): ?string
    {
        return $this->zboziType;
    }
}
