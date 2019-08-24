<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Filter;

use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterData as BaseProductFilterData;

class ProductFilterData extends BaseProductFilterData
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\Parameter\ParameterValue[]
     */
    public $colors = [];

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Parameter\ParameterValue[]
     */
    public $sizes = [];
}
