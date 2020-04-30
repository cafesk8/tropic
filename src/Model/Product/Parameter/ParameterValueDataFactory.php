<?php

declare(strict_types=1);

namespace App\Model\Product\Parameter;

use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValue;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValueData as BaseParameterValueData;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValueDataFactory as BaseParameterValueDataFactory;

class ParameterValueDataFactory extends BaseParameterValueDataFactory
{
    /**
     * @return \App\Model\Product\Parameter\ParameterValueData
     */
    public function create(): BaseParameterValueData
    {
        return new ParameterValueData();
    }

    /**
     * @param \App\Model\Product\Parameter\ParameterValue $parameterValue
     * @return \App\Model\Product\Parameter\ParameterValueData
     */
    public function createFromParameterValue(ParameterValue $parameterValue): BaseParameterValueData
    {
        $parameterValueData = new ParameterValueData();
        $this->fillFromParameterValue($parameterValueData, $parameterValue);

        return $parameterValueData;
    }

    /**
     * @param \App\Model\Product\Parameter\ParameterValueData $parameterValueData
     * @param \App\Model\Product\Parameter\ParameterValue $parameterValue
     */
    protected function fillFromParameterValue(BaseParameterValueData $parameterValueData, ParameterValue $parameterValue)
    {
        parent::fillFromParameterValue($parameterValueData, $parameterValue);

        $parameterValueData->rgb = $parameterValue->getRgb();
        $parameterValueData->mallName = $parameterValue->getMallName();
    }
}
