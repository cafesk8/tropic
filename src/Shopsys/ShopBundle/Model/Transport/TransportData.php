<?php

namespace Shopsys\ShopBundle\Model\Transport;

use Shopsys\FrameworkBundle\Model\Transport\TransportData as BaseTransportData;

class TransportData extends BaseTransportData
{
    /**
     * @var bool
     */
    public $balikobot;

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

    public function __construct()
    {
        parent::__construct();
        $this->balikobot = false;
        $this->pickupPlace = false;
    }
}
