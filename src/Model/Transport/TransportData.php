<?php

declare(strict_types=1);

namespace App\Model\Transport;

use Shopsys\FrameworkBundle\Model\Transport\TransportData as BaseTransportData;

/**
 * @property \App\Model\Payment\Payment[] $payments
 * @property \App\Model\Pricing\Vat\Vat[] $vatsIndexedByDomainId
 */
class TransportData extends BaseTransportData
{
    public ?string $balikobotShipper;

    public ?string $balikobotShipperService;

    public bool $pickupPlace;

    public bool $initialDownload;

    public string $transportType;

    /**
     * @var \App\Model\Country\Country[]
     */
    public array $countries;

    public ?string $mallType;

    public ?string $externalId;

    public ?string $trackingUrlPattern;

    public ?string $mergadoTransportType;

    public bool $bulkyAllowed;

    public bool $oversizedAllowed;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Money\Money[]|null[]
     */
    public array $actionPricesIndexedByDomainId;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Money\Money[]|null[]
     */
    public array $minActionOrderPricesIndexedByDomainId;

    /**
     * @var \DateTime[]|null[]
     */
    public array $actionDatesFromIndexedByDomainId;

    /**
     * @var \DateTime[]|null[]
     */
    public array $actionDatesToIndexedByDomainId;

    /**
     * @var bool[]
     */
    public array $actionActiveIndexedByDomainId;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Money\Money[]|null[]
     */
    public array $minFreeOrderPricesIndexedByDomainId;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Money\Money[]|null[]
     */
    public array $maxOrderPricesLimitIndexedByDomainId;

    public ?string $zboziType;

    public function __construct()
    {
        parent::__construct();
        $this->pickupPlace = false;
        $this->initialDownload = false;
        $this->transportType = Transport::TYPE_NONE;
        $this->bulkyAllowed = true;
        $this->oversizedAllowed = true;
        $this->actionPricesIndexedByDomainId = [];
        $this->minActionOrderPricesIndexedByDomainId = [];
        $this->actionDatesFromIndexedByDomainId = [];
        $this->actionDatesToIndexedByDomainId = [];
        $this->actionActiveIndexedByDomainId = [];
        $this->minFreeOrderPricesIndexedByDomainId = [];
        $this->maxOrderPricesLimitIndexedByDomainId = [];
        $this->balikobotShipperService = null;
        $this->balikobotShipper = null;
        $this->countries = [];
        $this->mallType = null;
        $this->externalId = null;
        $this->trackingUrlPattern = null;
        $this->mergadoTransportType = null;
        $this->zboziType = null;
    }
}
