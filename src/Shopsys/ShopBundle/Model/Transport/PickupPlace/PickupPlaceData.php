<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Transport\PickupPlace;

class PickupPlaceData
{
    /**
     * @var string|null
     */
    public $id;

    /**
     * @var string|null
     */
    public $balikobotId;

    /**
     * @var string|null
     */
    public $balikobotShipper;

    /**
     * @var string|null
     */
    public $balikobotShipperService;

    /**
     * @var string|null
     */
    public $name;

    /**
     * @var string|null
     */
    public $city;

    /**
     * @var string|null
     */
    public $street;

    /**
     * @var string|null
     */
    public $postCode;

    /**
     * @var string|null
     */
    public $countryCode;

    /**
     * @param \Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlace $pickupPlace
     */
    public function setFromEntity(PickupPlace $pickupPlace): void
    {
        $this->id = $pickupPlace->getId();
        $this->balikobotId = $pickupPlace->getBalikobotId();
        $this->balikobotShipper = $pickupPlace->getBalikobotShipper();
        $this->balikobotShipperService = $pickupPlace->getBalikobotShipperService();
        $this->name = $pickupPlace->getName();
        $this->city = $pickupPlace->getCity();
        $this->street = $pickupPlace->getStreet();
        $this->postCode = $pickupPlace->getPostCode();
        $this->countryCode = $pickupPlace->getCountryCode();
    }
}
