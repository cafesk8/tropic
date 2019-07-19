<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product;

use Shopsys\FrameworkBundle\Model\Product\ProductData as BaseProductData;

class ProductData extends BaseProductData
{
    /**
     * @var array
     */
    public $stockQuantityByStoreId = [];

    /**
     * @var string|null
     */
    public $transferNumber = null;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter|null
     */
    public $distinguishingParameter;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroup|null
     */
    public $mainVariantGroup;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter|null
     */
    public $distinguishingParameterForMainVariantGroup;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Product[]
     */
    public $productsInGroup;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Money\Money[]
     */
    public $actionPrices;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Product|null
     */
    public $gift;

    /**
     * @var bool
     */
    public $generateToHsSportXmlFeed;

    public function __construct()
    {
        parent::__construct();
        $this->productsInGroup = [];
        $this->actionPrices = [];
        $this->gift = null;
    }
}
