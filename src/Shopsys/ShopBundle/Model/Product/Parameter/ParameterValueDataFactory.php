<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Parameter;

use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValue;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValueData as BaseParameterValueData;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValueDataFactory as BaseParameterValueDataFactory;

class ParameterValueDataFactory extends BaseParameterValueDataFactory
{
    /**
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValueData
     */
    public function create(): BaseParameterValueData
    {
        return new ParameterValueData();
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValue $parameterValue
     * @return \Shopsys\ShopBundle\Model\Product\Parameter\ParameterValueData
     */
    public function createFromParameterValue(ParameterValue $parameterValue): BaseParameterValueData
    {
        $parameterValueData = new ParameterValueData();
        $this->fillFromParameterValue($parameterValueData, $parameterValue);

        return $parameterValueData;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\ParameterValueData $parameterValueData
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\ParameterValue $parameterValue
     */
    protected function fillFromParameterValue(BaseParameterValueData $parameterValueData, ParameterValue $parameterValue)
    {
        parent::fillFromParameterValue($parameterValueData, $parameterValue);

        $parameterValueData->hsFeedId = $parameterValue->getHsFeedId();
        $parameterValueData->rgb = $parameterValue->getRgb();
    }
}
