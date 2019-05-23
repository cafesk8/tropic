<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Balikobot\Pickup;

use Shopsys\ShopBundle\Component\Balikobot\BalikobotClient;
use Shopsys\ShopBundle\Component\Balikobot\Shipper\Exception\ShipperNotSupportedException;
use Shopsys\ShopBundle\Component\Balikobot\Shipper\ShipperFacade;

class PickupFacade
{
    const BRANCHES_REQUEST = 'branches';

    /**
     * @var \Shopsys\ShopBundle\Component\Balikobot\Shipper\ShipperFacade
     */
    private $shipperFacade;

    /**
     * @var \Shopsys\ShopBundle\Component\Balikobot\BalikobotClient
     */
    private $balikobotClient;

    /**
     * @param \Shopsys\ShopBundle\Component\Balikobot\BalikobotClient $balikobotClient
     * @param \Shopsys\ShopBundle\Component\Balikobot\Shipper\ShipperFacade $shipperFacade
     */
    public function __construct(BalikobotClient $balikobotClient, ShipperFacade $shipperFacade)
    {
        $this->shipperFacade = $shipperFacade;
        $this->balikobotClient = $balikobotClient;
    }

    /**
     * @param string $shipper
     * @param string|null $shipperService
     * @return array|null
     */
    public function getPickupPlaces(string $shipper, ?string $shipperService): ?array
    {
        if ($this->shipperFacade->isShipperAllowed($shipper) === false) {
            throw new ShipperNotSupportedException(sprintf('Shipper `%s` is not supported', $shipper));
        }

        $data = $this->balikobotClient->request(self::BRANCHES_REQUEST, $shipper, [], $shipperService);

        return $data['branches'];
    }

    /**
     * @param string $shipper
     * @param string|null $shipperService
     * @return bool
     */
    public function isPickUpPlaceShipping(string $shipper, ?string $shipperService): bool
    {
        $places = $this->getPickupPlaces($shipper, $shipperService);

        if ($places === null) {
            return false;
        }

        return true;
    }
}
