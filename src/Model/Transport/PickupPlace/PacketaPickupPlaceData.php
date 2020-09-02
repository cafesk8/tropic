<?php

declare(strict_types=1);

namespace App\Model\Transport\PickupPlace;

class PacketaPickupPlaceData implements PickupPlaceInterface
{
    public int $id;

    public string $name;

    public string $city;

    public string $street;

    public string $postcode;

    public string $countryCode;

    /**
     * @param int $id
     * @param string $name
     * @param string $city
     * @param string $street
     * @param string $postCode
     * @param string $countryCode
     */
    public function __construct(int $id, string $name, string $city, string $street, string $postCode, string $countryCode)
    {
        $this->id = $id;
        $this->name = $name;
        $this->city = $city;
        $this->street = $street;
        $this->postcode = $postCode;
        $this->countryCode = $countryCode;
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
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getFullAddress(): string
    {
        return $this->street . ', ' . $this->postcode . ', ' . $this->city;
    }

    /**
     * @return string|null
     */
    public function getStreet(): ?string
    {
        return $this->street;
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
    public function getPostCode(): string
    {
        return $this->postcode;
    }
}
