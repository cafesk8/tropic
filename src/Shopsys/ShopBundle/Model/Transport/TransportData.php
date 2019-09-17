<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Transport;

use Shopsys\FrameworkBundle\Model\Transport\TransportData as BaseTransportData;

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
    public $personalTakeType;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Country\Country[]
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

    public function __construct()
    {
        parent::__construct();
        $this->pickupPlace = false;
        $this->initialDownload = false;
        $this->personalTakeType = Transport::PERSONAL_TAKE_TYPE_NONE;
        $this->deliveryDays = 0;
    }
}
