<?php

declare(strict_types=1);

namespace App\Model\Product\ProductGift;

class ProductGiftData
{
    /**
     * @var \App\Model\Product\Product|null
     */
    public $gift;

    /**
     * @var \App\Model\Product\Product[]
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

    /**
     * @var string|null
     */
    public $title;
}
