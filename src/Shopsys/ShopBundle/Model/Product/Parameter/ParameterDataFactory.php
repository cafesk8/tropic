<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Parameter;

use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterData as BaseParameterData;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterDataFactory as BaseParameterDataFactory;

class ParameterDataFactory extends BaseParameterDataFactory
{
    /**
     * @return \Shopsys\ShopBundle\Model\Product\Parameter\ParameterData
     */
    public function create(): BaseParameterData
    {
        $parameterData = new ParameterData();
        $this->fillNew($parameterData);

        return $parameterData;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\Parameter $parameter
     * @return \Shopsys\ShopBundle\Model\Product\Parameter\ParameterData
     */
    public function createFromParameter(Parameter $parameter): BaseParameterData
    {
        $parameterData = new ParameterData();
        $this->fillFromParameter($parameterData, $parameter);

        return $parameterData;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\ParameterData $parameterData
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\Parameter $parameter
     */
    protected function fillFromParameter(BaseParameterData $parameterData, Parameter $parameter): void
    {
        parent::fillFromParameter($parameterData, $parameter);

        $parameterData->visibleOnFrontend = $parameter->isVisibleOnFrontend();
    }
}
