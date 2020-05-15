<?php

declare(strict_types=1);

namespace App\Model\Product\Flag;

class ProductFlagData
{
    /**
     * @var \App\Model\Product\Product
     */
    public $product;

    /**
     * @var \App\Model\Product\Flag\Flag
     */
    public $flag;

    /**
     * @var \DateTime|null
     */
    public $activeFrom;

    /**
     * @var \DateTime|null
     */
    public $activeTo;
}
