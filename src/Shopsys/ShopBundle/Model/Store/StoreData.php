<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Store;

use Shopsys\FrameworkBundle\Component\FileUpload\ImageUploadData;

class StoreData
{
    /**
     * @var int
     */
    public $domainId;

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
     * @var string|null
     */
    public $position;

    /**
     * @var \Shopsys\FrameworkBundle\Component\FileUpload\ImageUploadData
     */
    public $images;

    public function __construct()
    {
        $this->images = new ImageUploadData();
    }
}