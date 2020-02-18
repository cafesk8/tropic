<?php

declare(strict_types=1);

namespace App\Model\Transport;

use Shopsys\FrameworkBundle\Model\Transport\TransportData as BaseTransportData;

/**
 * @property \App\Model\Payment\Payment[] $payments
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
     * @var int
     */
    public $deliveryDays;

    /**
     * @var string|null
     */
    public $externalId;

    /**
     * @var string|null
     */
    public $trackingUrlPattern;

    public function __construct()
    {
        parent::__construct();
        $this->pickupPlace = false;
        $this->initialDownload = false;
        $this->transportType = Transport::TYPE_NONE;
        $this->deliveryDays = 0;
    }
}
