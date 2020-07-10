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
    /**
     * @var string|null
     */
    public $balikobotShipper;

    /**
     * @var string|null
     */
    public $balikobotShipperService;

    /**
     * @var bool
     */
    public $pickupPlace;

    /**
     * @var bool
     */
    public $initialDownload;

    /**
     * @var string
     */
    public $transportType;

    /**
     * @var \App\Model\Country\Country[]
     */
    public $countries;

    /**
     * @var string
     */
    public $mallType;

    /**
     * @var string|null
     */
    public $externalId;

    /**
     * @var string|null
     */
    public $trackingUrlPattern;

    /**
     * @var string|null
     */
    public $mergadoTransportType;

    /**
     * @var bool
     */
    public bool $bulkyAllowed;

    /**
     * @var bool
     */
    public bool $oversizedAllowed;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Money\Money[]|null[]
     */
    public array $actionPricesIndexedByDomainId;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Money\Money[]|null[]
     */
    public array $minOrderPricesIndexedByDomainId;

    /**
     * @var \DateTime[]|null[]
     */
    public array $actionDatesFromIndexedByDomainId;

    /**
     * @var \DateTime[]|null[]
     */
    public array $actionDatesToIndexedByDomainId;

    public function __construct()
    {
        parent::__construct();
        $this->pickupPlace = false;
        $this->initialDownload = false;
        $this->transportType = Transport::TYPE_NONE;
        $this->bulkyAllowed = true;
        $this->oversizedAllowed = true;
        $this->actionPricesIndexedByDomainId = [];
        $this->minOrderPricesIndexedByDomainId = [];
        $this->actionDatesFromIndexedByDomainId = [];
        $this->actionDatesToIndexedByDomainId = [];
    }
}
