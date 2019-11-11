<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\ProductGift;

class ProductGiftData
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\Product|null
     */
    public $gift;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Product[]
     */
    public $products;

    /**
     * @var int|null
     */
    public $domainId;

    /**
     * @var bool|null
     */
    public $active;
}
