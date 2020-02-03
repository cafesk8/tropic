<?php

declare(strict_types=1);

namespace App\Model\Product\Parameter;

use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter as BaseParameter;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterData as BaseParameterData;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterDataFactory as BaseParameterDataFactory;

class ParameterDataFactory extends BaseParameterDataFactory
{
    /**
     * @return \App\Model\Product\Parameter\ParameterData
     */
    public function create(): BaseParameterData
    {
        $parameterData = new ParameterData();
        $this->fillNew($parameterData);
        $parameterData->type = Parameter::TYPE_DEFAULT;
        $parameterData->mallId = null;

        return $parameterData;
    }

    /**
     * @param \App\Model\Product\Parameter\Parameter $parameter
     * @return \App\Model\Product\Parameter\ParameterData
     */
    public function createFromParameter(BaseParameter $parameter): BaseParameterData
    {
        $parameterData = new ParameterData();
        $this->fillFromParameter($parameterData, $parameter);

        return $parameterData;
    }

    /**
     * @param \App\Model\Product\Parameter\ParameterData $parameterData
     * @param \App\Model\Product\Parameter\Parameter $parameter
     */
    protected function fillFromParameter(BaseParameterData $parameterData, BaseParameter $parameter): void
    {
        parent::fillFromParameter($parameterData, $parameter);

        $parameterData->type = $parameter->getType();
        $parameterData->mallId = $parameter->getMallId();
        $parameterData->visibleOnFrontend = $parameter->isVisibleOnFrontend();
        $parameterData->mallId = $parameter->getMallId();
    }
}
