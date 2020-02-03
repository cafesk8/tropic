<?php

declare(strict_types=1);

namespace App\Model\Product\Filter;

use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterData as BaseProductFilterData;

/**
 * @property \App\Model\Product\Flag\Flag[] $flags
 * @property \App\Model\Product\Brand\Brand[] $brands
 */
class ProductFilterData extends BaseProductFilterData
{
    /**
     * @var \App\Model\Product\Parameter\ParameterValue[]
     */
    public $colors = [];

    /**
     * @var \App\Model\Product\Parameter\ParameterValue[]
     */
    public $sizes = [];
}
