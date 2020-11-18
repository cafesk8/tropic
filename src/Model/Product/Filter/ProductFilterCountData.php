<?php

declare(strict_types=1);

namespace App\Model\Product\Filter;

use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterCountData as BaseProductFilterCountData;

class ProductFilterCountData extends BaseProductFilterCountData
{
    public int $countAvailable;
}