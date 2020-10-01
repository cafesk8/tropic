<?php

declare(strict_types=1);

namespace App\Model\Product\Parameter;

use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValue;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValueFactory as BaseProductParameterValueFactory;
use Shopsys\FrameworkBundle\Model\Product\Product;

class ProductParameterValueFactory extends BaseProductParameterValueFactory
{
    /**
     * @param \App\Model\Product\Product $product
     * @param \App\Model\Product\Parameter\Parameter $parameter
     * @param \App\Model\Product\Parameter\ParameterValue $value
     * @param int|null $position
     * @param bool $takenFromMainVariant
     * @return \App\Model\Product\Parameter\ProductParameterValue
     */
    public function create(
        Product $product,
        Parameter $parameter,
        ParameterValue $value,
        ?int $position = null,
        bool $takenFromMainVariant = false
    ): ProductParameterValue {
        return new ProductParameterValue($product, $parameter, $value, $position, $takenFromMainVariant);
    }
}
