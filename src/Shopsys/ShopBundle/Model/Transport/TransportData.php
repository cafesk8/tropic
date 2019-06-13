<?php

namespace Shopsys\ShopBundle\Model\Transport;

use Shopsys\FrameworkBundle\Model\Transport\TransportData as BaseTransportData;
use Shopsys\ShopBundle\Form\Admin\TransportFormTypeExtension;

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

    public function __construct()
    {
        parent::__construct();
        $this->pickupPlace = false;
        $this->initialDownload = false;
        $this->personalTakeType = TransportFormTypeExtension::PERSONAL_TAKE_TYPE_NONE;
    }
}
