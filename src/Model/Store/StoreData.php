<?php

declare(strict_types=1);

namespace App\Model\Store;

use Shopsys\FrameworkBundle\Component\FileUpload\ImageUploadData;

class StoreData
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string|null
     */
    public $description;

    /**
     * @var string|null
     */
    public $street;

    /**
     * @var string|null
     */
    public $city;

    /**
     * @var string|null
     */
    public $postcode;

    /**
     * @var string|null
     */
    public $openingHours;

    /**
     * @var string|null
     */
    public $googleMapsLink;

    /**
     * @var int|null
     */
    public $position;

    /**
     * @var \Shopsys\FrameworkBundle\Component\FileUpload\ImageUploadData
     */
    public $images;

    /**
     * @var \App\Model\Country\Country|null
     */
    public $country;

    /**
     * @var bool
     */
    public $pickupPlace;

    /**
     * @var string|null
     */
    public $email;

    /**
     * @var string|null
     */
    public $telephone;

    /**
     * @var string|null
     */
    public $region;

    /**
     * @var string|null
     */
    public $externalNumber;

    /**
     * @var bool
     */
    public $showOnStoreList;

    /**
     * @var bool
     */
    public $centralStore;

    /**
     * @var string|null
     */
    public $pohodaName;

    public function __construct()
    {
        $this->images = new ImageUploadData();
        $this->pickupPlace = false;
        $this->showOnStoreList = true;
        $this->centralStore = false;
    }
}
