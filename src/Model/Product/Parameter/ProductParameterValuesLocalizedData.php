<?php

declare(strict_types=1);

namespace App\Model\Product\Parameter;

use Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValuesLocalizedData as BaseProductParameterValuesLocalizedData;

/**
 * @property \App\Model\Product\Parameter\Parameter|null $parameter
 */
class ProductParameterValuesLocalizedData extends BaseProductParameterValuesLocalizedData
{
    public ?int $position = null;

    public bool $takenFromMainVariant = false;
}
