<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Parameter;

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
     * @ORM\Column(type="string", nullable=true)
     */
    protected $hsFeedId;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    protected $rgb;

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\ParameterValueData $parameterData
     */
    public function __construct(ParameterValueData $parameterData)
    {
        parent::__construct($parameterData);

        $this->hsFeedId = $parameterData->hsFeedId;
        $this->rgb = $parameterData->rgb;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\ParameterValueData $parameterData
     */
    public function edit(ParameterValueData $parameterData)
    {
        parent::edit($parameterData);

        $this->hsFeedId = $parameterData->hsFeedId;
        $this->rgb = $parameterData->rgb;
    }

    /**
     * @return string|null
     */
    public function getHsFeedId(): ?string
    {
        return $this->hsFeedId;
    }

    /**
     * @return string|null
     */
    public function getRgb(): ?string
    {
        return $this->rgb;
    }
}
