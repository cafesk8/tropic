<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Filter;

use Shopsys\FrameworkBundle\Model\Product\Filter\PriceRange;
use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfig as BaseProductFilterConfig;

class ProductFilterConfig extends BaseProductFilterConfig
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\Parameter\ParameterValue[]
     */
    private $colorChoices;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Parameter\ParameterValue[]
     */
    private $sizeChoices;

    /**
     * ProductFilterConfig constructor.
     * @param \Shopsys\FrameworkBundle\Model\Product\Filter\ParameterFilterChoice[] $parameterChoices
     * @param \Shopsys\ShopBundle\Model\Product\Flag\Flag[] $flagChoices
     * @param \Shopsys\ShopBundle\Model\Product\Brand\Brand[] $brandChoices
     * @param \Shopsys\FrameworkBundle\Model\Product\Filter\PriceRange $priceRange
     * @param \Shopsys\FrameworkBundle\Model\Product\Filter\ParameterFilterChoice[] $colorChoices
     * @param \Shopsys\FrameworkBundle\Model\Product\Filter\ParameterFilterChoice[] $sizeChoices
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
     * @return \Shopsys\ShopBundle\Model\Product\Parameter\ParameterValue[]
     */
    public function getColorChoices(): array
    {
        return $this->colorChoices;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Product\Parameter\ParameterValue[]
     */
    public function getSizeChoices(): array
    {
        return $this->sizeChoices;
    }
}
