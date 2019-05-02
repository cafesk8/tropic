<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Store;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="stores")
 */
class Store
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
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $domainId;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $name;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $street;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $city;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=30, nullable=true)
     */
    private $postcode;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $openingHours;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $googleMapsLink;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $position;

    /**
     * @param \Shopsys\ShopBundle\Model\Store\StoreData $storeData
     */
    public function __construct(StoreData $storeData)
    {
        $this->name = $storeData->name;
        $this->domainId = $storeData->domainId;
        $this->description = $storeData->description;
        $this->street = $storeData->street;
        $this->city = $storeData->city;
        $this->postcode = $storeData->postcode;
        $this->openingHours = $storeData->openingHours;
        $this->googleMapsLink = $storeData->googleMapsLink;
        $this->position = $storeData->position;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Store\StoreData $storeData
     * @return \Shopsys\ShopBundle\Model\Store\Store
     */
    public static function create(StoreData $storeData): self
    {
        return new static($storeData);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Store\StoreData $storeData
     */
    public function edit(StoreData $storeData): void
    {
        $this->name = $storeData->name;
        $this->domainId = $storeData->domainId;
        $this->description = $storeData->description;
        $this->street = $storeData->street;
        $this->city = $storeData->city;
        $this->postcode = $storeData->postcode;
        $this->openingHours = $storeData->openingHours;
        $this->googleMapsLink = $storeData->googleMapsLink;
        $this->position = $storeData->position;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getDomainId(): int
    {
        return $this->domainId;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return string|null
     */
    public function getStreet(): ?string
    {
        return $this->street;
    }

    /**
     * @return string|null
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @return string|null
     */
    public function getPostcode(): ?string
    {
        return $this->postcode;
    }

    /**
     * @return string|null
     */
    public function getOpeningHours(): ?string
    {
        return $this->openingHours;
    }

    /**
     * @return string|null
     */
    public function getGoogleMapsLink(): ?string
    {
        return $this->googleMapsLink;
    }

    /**
     * @return int|null
     */
    public function getPosition(): ?int
    {
        return $this->position;
    }
}
