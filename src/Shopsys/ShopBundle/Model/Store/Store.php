<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Store;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Country\Country;
use Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlaceInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="stores")
 */
class Store implements PickupPlaceInterface
{
    /**
     * Those stores have special function and we are not downloading their stock quantities
     */
    public const SPECIAL_STORES_NOT_ON_ESHOP = ['1010', '0039', '0040'];

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
     * @var string
     *
     * @ORM\Column(type="string", length=30)
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
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $pickupPlace;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Country\Country|null
     * @ORM\ManyToOne(targetEntity="Shopsys\FrameworkBundle\Model\Country\Country")
     * @ORM\JoinColumn(nullable=false, name="country_id", referencedColumnName="id")
     */
    protected $country;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $email;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=30, nullable=true)
     */
    protected $telephone;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $region;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $externalNumber;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $showOnStoreList;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $franchisor;

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
        $this->country = $storeData->country;
        $this->pickupPlace = $storeData->pickupPlace;
        $this->telephone = $storeData->telephone;
        $this->email = $storeData->email;
        $this->region = $storeData->region;
        $this->externalNumber = $storeData->externalNumber;
        $this->showOnStoreList = $storeData->showOnStoreList;
        $this->franchisor = $storeData->franchisor;
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
        $this->country = $storeData->country;
        $this->pickupPlace = $storeData->pickupPlace;
        $this->telephone = $storeData->telephone;
        $this->email = $storeData->email;
        $this->region = $storeData->region;
        $this->externalNumber = $storeData->externalNumber;
        $this->showOnStoreList = $storeData->showOnStoreList;
        $this->franchisor = $storeData->franchisor;
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
     * @return string
     */
    public function getStreet(): string
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
     * @return string
     */
    public function getPostcode(): string
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

    /**
     * @return string
     */
    public function getFullAddress(): string
    {
        return $this->street . ', ' . $this->postcode . ' ' . $this->city;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Country\Country|null
     */
    public function getCountry(): Country
    {
        return $this->country;
    }

    /**
     * @return string
     */
    public function getCountryCode(): string
    {
        return $this->country->getCode();
    }

    /**
     * @return bool
     */
    public function isPickupPlace(): bool
    {
        return $this->pickupPlace;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @return string|null
     */
    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    /**
     * @return string|null
     */
    public function getRegion(): ?string
    {
        return $this->region;
    }

    /**
     * @return string|null
     */
    public function getExternalNumber(): ?string
    {
        return $this->externalNumber;
    }

    /**
     * @return bool
     */
    public function isShowOnStoreList(): bool
    {
        return $this->showOnStoreList;
    }

    /**
     * @return bool
     */
    public function isFranchisor(): bool
    {
        return $this->franchisor;
    }
}
