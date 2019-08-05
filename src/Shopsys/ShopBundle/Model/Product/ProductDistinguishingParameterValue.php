<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product;

use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValue;

class ProductDistinguishingParameterValue
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValue|null
     */
    private $colorParameterValue;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValue|null
     */
    private $sizeParameterValue;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValue|null $colorParameterValue
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValue|null $sizeParameterValue
     */
    public function __construct(?ParameterValue $colorParameterValue, ?ParameterValue $sizeParameterValue)
    {
        $this->colorParameterValue = $colorParameterValue;
        $this->sizeParameterValue = $sizeParameterValue;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValue|null
     */
    public function getColorParameterValue(): ?ParameterValue
    {
        return $this->colorParameterValue;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValue|null
     */
    public function getSizeParameterValue(): ?ParameterValue
    {
        return $this->sizeParameterValue;
    }
}
