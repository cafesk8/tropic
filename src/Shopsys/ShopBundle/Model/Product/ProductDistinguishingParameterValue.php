<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product;

class ProductDistinguishingParameterValue
{
    /**
     * @var string|null
     */
    private $colorParameterValue;

    /**
     * @var string|null
     */
    private $sizeParameterValue;

    /**
     * @param string|null $colorParameterValue
     * @param string|null $sizeParameterValue
     */
    public function __construct(?string $colorParameterValue, ?string $sizeParameterValue)
    {
        $this->colorParameterValue = $colorParameterValue;
        $this->sizeParameterValue = $sizeParameterValue;
    }

    /**
     * @return string|null
     */
    public function getColorParameterValue(): ?string
    {
        return $this->colorParameterValue;
    }

    /**
     * @return string|null
     */
    public function getSizeParameterValue(): ?string
    {
        return $this->sizeParameterValue;
    }
}
