<?php

declare(strict_types=1);

namespace App\Model\Store;

use App\Component\Transfer\Pohoda\Product\PohodaProductExportRepository;
use App\Model\Transport\PickupPlace\PickupPlaceInterface;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Shopsys\FrameworkBundle\Model\Country\Country;

/**
 * @ORM\Entity
 * @ORM\Table(name="stores")
 */
class Store implements PickupPlaceInterface
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
     * @ORM\Column(type="integer", nullable=false)
     * @Gedmo\SortablePosition
     */
    private $position;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $pickupPlace;

    /**
     * @var \App\Model\Country\Country|null
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
    private $centralStore;

    /**
     * @param \App\Model\Store\StoreData $storeData
     */
    public function __construct(StoreData $storeData)
    {
        $this->name = $storeData->name;
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
        $this->centralStore = $storeData->centralStore;
    }

    /**
     * @param \App\Model\Store\StoreData $storeData
     * @return \App\Model\Store\Store
     */
    public static function create(StoreData $storeData): self
    {
        return new self($storeData);
    }

    /**
     * @param \App\Model\Store\StoreData $storeData
     */
    public function edit(StoreData $storeData): void
    {
        $this->name = $storeData->name;
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
        $this->centralStore = $storeData->centralStore;
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
     * @return \App\Model\Country\Country|null
     */
    public function getCountry(): ?Country
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
    public function isCentralStore(): bool
    {
        return $this->centralStore;
    }

    /**
     * @return bool
     */
    public function isExternalStock(): bool
    {
        return (int)$this->externalNumber === PohodaProductExportRepository::POHODA_STOCK_EXTERNAL_ID;
    }
}
