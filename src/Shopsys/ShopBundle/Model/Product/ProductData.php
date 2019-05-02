<?php

namespace Shopsys\ShopBundle\Model\Product;

use Shopsys\FrameworkBundle\Model\Product\ProductData as BaseProductData;

class ProductData extends BaseProductData
{
    /**
     * @var array
     */
    public $stockQuantityByStoreId = [];

    /**
     * @var int|null
     */
    public $transferNumber = null;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter|null
     */
    public $distinguishingParameter;
}
