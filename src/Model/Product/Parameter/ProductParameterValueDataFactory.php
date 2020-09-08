<?php

declare(strict_types=1);

namespace App\Model\Product\Parameter;

use Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValue;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValueData as BaseProductParameterValueData;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValueDataFactory as BaseProductParameterValueDataFactory;

/**
 * @method \App\Model\Product\Parameter\ProductParameterValueData create()
 * @method \App\Model\Product\Parameter\ProductParameterValueData createFromProductParameterValue(\App\Model\Product\Parameter\ProductParameterValue $productParameterValue)
 */
class ProductParameterValueDataFactory extends BaseProductParameterValueDataFactory
{
    /**
     * @param \App\Model\Product\Parameter\ProductParameterValueData $productParameterValueData
     * @param \App\Model\Product\Parameter\ProductParameterValue $productParameterValue
     */
    protected function fillFromProductParameterValue(
        BaseProductParameterValueData $productParameterValueData,
        ProductParameterValue $productParameterValue
    ): void {
        parent::fillFromProductParameterValue($productParameterValueData, $productParameterValue);
        $productParameterValueData->position = $productParameterValue->getPosition();
        $productParameterValueData->takenFromMainVariant = $productParameterValue->isTakenFromMainVariant();
    }

    /**
     * @return \App\Model\Product\Parameter\ProductParameterValueData
     */
    protected function createInstance(): ProductParameterValueData
    {
        return new ProductParameterValueData();
    }
}
