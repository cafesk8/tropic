<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Balikobot\Shipper;

use Shopsys\ShopBundle\Component\Balikobot\BalikobotClient;

class ShipperFacade
{
    const
        SHIPPER_CESKA_POSTA = 'cp',
        SHIPPER_DPD = 'dpd',
        SHIPPER_GEIS = 'geis',
        SHIPPER_GLS = 'gls',
        SHIPPER_INTIME = 'intime',
        SHIPPER_POSTA_BEZ_HRANIC = 'pbh',
        SHIPPER_PPL = 'PPL',
        SHIPPER_TOPTRANS = 'toptrans',
        SHIPPER_ULOZENKA = 'ulozenka',
        SHIPPER_ZASILKOVNA = 'zasilkovna';

    /**
     * @var \Shopsys\ShopBundle\Component\Balikobot\BalikobotClient
     */
    private $client;

    /**
     * @param \Shopsys\ShopBundle\Component\Balikobot\BalikobotClient $client
     */
    public function __construct(BalikobotClient $client)
    {
        $this->client = $client;
    }
}
