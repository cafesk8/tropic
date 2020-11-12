<?php

declare(strict_types=1);

namespace App\Model\Advert;

use Shopsys\FrameworkBundle\Component\FileUpload\ImageUploadData;
use Shopsys\FrameworkBundle\Model\Advert\AdvertData as BaseAdvertData;

class AdvertData extends BaseAdvertData
{
    /**
     * @var string|null
     */
    public $smallTitle;

    /**
     * @var string|null
     */
    public $bigTitle;

    /**
     * @var string|null
     */
    public $productTitle;

    /**
     * @var \App\Model\Product\Product[]
     */
    public $products;

    /**
     * @var \App\Model\Category\Category[]
     */
    public $categories;

    public ImageUploadData $mobileImage;

    public function __construct()
    {
        parent::__construct();

        $this->products = [];
        $this->categories = [];
        $this->mobileImage = new ImageUploadData();
    }
}
