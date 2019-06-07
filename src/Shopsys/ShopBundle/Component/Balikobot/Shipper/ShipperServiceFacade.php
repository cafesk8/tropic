<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Balikobot\Shipper;

use Shopsys\ShopBundle\Component\Balikobot\BalikobotClient;
use Shopsys\ShopBundle\Component\Balikobot\Shipper\Exception\ShipperNotSupportedException;

class ShipperServiceFacade
{
    const SERVICES_REQUEST = 'services';

    /**
     * @var \Shopsys\ShopBundle\Component\Balikobot\BalikobotClient
     */
    private $client;

    /**
     * @var \Shopsys\ShopBundle\Component\Balikobot\Shipper\ShipperFacade
     */
    private $shipperFacade;

    /**
     * @param \Shopsys\ShopBundle\Component\Balikobot\BalikobotClient $client
     * @param \Shopsys\ShopBundle\Component\Balikobot\Shipper\ShipperFacade $shipperFacade
     */
    public function __construct(BalikobotClient $client, ShipperFacade $shipperFacade)
    {
        $this->client = $client;
        $this->shipperFacade = $shipperFacade;
    }

    /**
     * @param string $shipper
     * @return string[]
     */
    public function getServicesForShipper(string $shipper): array
    {
        if ($this->shipperFacade->isShipperAllowed($shipper) === false) {
            throw new ShipperNotSupportedException(sprintf('Shipper `%s` is not supported', $shipper));
        }

        $data = $this->client->request(self::SERVICES_REQUEST, $shipper);

        $responseData = [];

        foreach ($data['service_types'] as $id => $service) {
            $responseData[(string)$id] = $service;
        }

        return $responseData;
    }
}
