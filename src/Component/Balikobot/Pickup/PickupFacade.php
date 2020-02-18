<?php

declare(strict_types=1);

namespace App\Component\Balikobot\Pickup;

use App\Component\Balikobot\BalikobotClient;
use App\Component\Balikobot\Shipper\Exception\ShipperNotSupportedException;
use App\Component\Balikobot\Shipper\ShipperFacade;

class PickupFacade
{
    public const BRANCHES_REQUEST = 'branches';

    /**
     * @var \App\Component\Balikobot\Shipper\ShipperFacade
     */
    private $shipperFacade;

    /**
     * @var \App\Component\Balikobot\BalikobotClient
     */
    private $balikobotClient;

    /**
     * @param \App\Component\Balikobot\BalikobotClient $balikobotClient
     * @param \App\Component\Balikobot\Shipper\ShipperFacade $shipperFacade
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

        return array_key_exists('branches', $data) ? $data['branches'] : null;
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
