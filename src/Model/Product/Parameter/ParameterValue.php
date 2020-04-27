<?php

declare(strict_types=1);

namespace App\Model\Product\Parameter;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValue as BaseParameterValue;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValueData;

/**
 * @ORM\Table(name="parameter_values")
 * @ORM\Entity
 */
class ParameterValue extends BaseParameterValue
{
    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    protected $rgb;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $mallName;

    /**
     * @param \App\Model\Product\Parameter\ParameterValueData $parameterData
     */
    public function __construct(ParameterValueData $parameterData)
    {
        parent::__construct($parameterData);

        $this->rgb = $parameterData->rgb;
        $this->mallName = $parameterData->mallName;
    }

    /**
     * @param \App\Model\Product\Parameter\ParameterValueData $parameterData
     */
    public function edit(ParameterValueData $parameterData)
    {
        parent::edit($parameterData);

        $this->rgb = $parameterData->rgb;
        $this->mallName = $parameterData->mallName;
    }

    /**
     * @return string|null
     */
    public function getRgb(): ?string
    {
        return $this->rgb;
    }

    /**
     * @return string|null
     */
    public function getMallName(): ?string
    {
        return $this->mallName;
    }
}
