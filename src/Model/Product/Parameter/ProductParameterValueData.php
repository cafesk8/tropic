<?php

declare(strict_types=1);

namespace App\Model\Product\Parameter;

use Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValueData as BaseProductParameterValueData;

/**
 * @property \App\Model\Product\Parameter\Parameter|null $parameter
 * @property \App\Model\Product\Parameter\ParameterValueData|null $parameterValueData
 */
class ProductParameterValueData extends BaseProductParameterValueData
{
    public ?int $position = null;

    public bool $takenFromMainVariant = false;
}
