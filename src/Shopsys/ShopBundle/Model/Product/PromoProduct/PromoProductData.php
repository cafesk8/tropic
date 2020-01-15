<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\PromoProduct;

class PromoProductData
{
    /**
     * @var int|null
     */
    public $domainId;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Product|null
     */
    public $product;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    public $price;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    public $minimalCartPrice;

    /**
     * @var string|null
     */
    public $type;

    public function __construct()
    {
        $this->type = PromoProduct::TYPE_ALL;
    }
}
