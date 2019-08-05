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
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValue|null $colorParameterValue
     */
    public function __construct(?ParameterValue $colorParameterValue)
    {
        $this->colorParameterValue = $colorParameterValue;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValue|null
     */
    public function getColorParameterValue(): ?ParameterValue
    {
        return $this->colorParameterValue;
    }
}
