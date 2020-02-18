<?php

declare(strict_types=1);

namespace App\Model\Product\Filter;

use Shopsys\FrameworkBundle\Model\Product\Filter\PriceRange;
use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfig as BaseProductFilterConfig;

/**
 * @property \App\Model\Product\Flag\Flag[] $flagChoices
 * @property \App\Model\Product\Brand\Brand[] $brandChoices
 * @method \App\Model\Product\Flag\Flag[] getFlagChoices()
 * @method \App\Model\Product\Brand\Brand[] getBrandChoices()
 */
class ProductFilterConfig extends BaseProductFilterConfig
{
    /**
     * @var \App\Model\Product\Parameter\ParameterValue[]
     */
    private $colorChoices;

    /**
     * @var \App\Model\Product\Parameter\ParameterValue[]
     */
    private $sizeChoices;

    /**
     * ProductFilterConfig constructor.
     * @param \Shopsys\FrameworkBundle\Model\Product\Filter\ParameterFilterChoice[] $parameterChoices
     * @param \App\Model\Product\Flag\Flag[] $flagChoices
     * @param \App\Model\Product\Brand\Brand[] $brandChoices
     * @param \Shopsys\FrameworkBundle\Model\Product\Filter\PriceRange $priceRange
     * @param \App\Model\Product\Parameter\ParameterValue[] $colorChoices
     * @param \App\Model\Product\Parameter\ParameterValue[] $sizeChoices
     */
    public function __construct(
        array $parameterChoices,
        array $flagChoices,
        array $brandChoices,
        PriceRange $priceRange,
        array $colorChoices,
        array $sizeChoices
    ) {
        parent::__construct($parameterChoices, $flagChoices, $brandChoices, $priceRange);
        $this->colorChoices = $colorChoices;
        $this->sizeChoices = $sizeChoices;
    }

    /**
     * @return \App\Model\Product\Parameter\ParameterValue[]
     */
    public function getColorChoices(): array
    {
        return $this->colorChoices;
    }

    /**
     * @return \App\Model\Product\Parameter\ParameterValue[]
     */
    public function getSizeChoices(): array
    {
        return $this->sizeChoices;
    }
}
