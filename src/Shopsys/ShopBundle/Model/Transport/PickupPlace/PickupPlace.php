<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Transport\PickupPlace;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="transport_pickup_places",
 * )
 * @ORM\Entity
 */
class PickupPlace
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $balikobotId;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $balikobotShipper;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $balikobotShipperService;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=250)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=250)
     */
    private $city;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=250)
     */
    private $street;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=30)
     */
    private $postCode;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=10)
     */
    private $countryCode;

    /**
     * @param \Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlaceData $pickupPlaceData
     */
    public function __construct(PickupPlaceData $pickupPlaceData)
    {
        $this->balikobotId = $pickupPlaceData->balikobotId;
        $this->balikobotShipper = $pickupPlaceData->balikobotShipper;
        $this->balikobotShipperService = $pickupPlaceData->balikobotShipperService;

        $this->edit($pickupPlaceData);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlaceData $pickupPlaceData
     */
    public function edit(PickupPlaceData $pickupPlaceData): void
    {
        $this->name = $pickupPlaceData->name;
        $this->city = $pickupPlaceData->city;
        $this->street = $pickupPlaceData->street;
        $this->postCode = $pickupPlaceData->postCode;
        $this->countryCode = $pickupPlaceData->countryCode;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getBalikobotId(): string
    {
        return $this->balikobotId;
    }

    /**
     * @return string
     */
    public function getBalikobotShipper(): string
    {
        return $this->balikobotShipper;
    }

    /**
     * @return string|null
     */
    public function getBalikobotShipperService(): ?string
    {
        return $this->balikobotShipperService;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * @return string
     */
    public function getStreet(): string
    {
        return $this->street;
    }

    /**
     * @return string
     */
    public function getPostCode(): string
    {
        return $this->postCode;
    }

    /**
     * @return string
     */
    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    /**
     * @return string
     */
    public function getFullAddress(): string
    {
        return $this->street . ', ' . $this->postCode . ' ' . $this->city;
    }
}
