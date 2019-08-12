<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Advert;

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
     * @var \Shopsys\ShopBundle\Model\Product\Product[]
     */
    public $products;

    public function __construct()
    {
        parent::__construct();

        $this->products = [];
    }
}
