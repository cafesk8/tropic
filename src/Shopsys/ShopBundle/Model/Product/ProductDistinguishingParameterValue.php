<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product;

use Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValue;

class ProductDistinguishingParameterValue
{
    /**
     * @var string|null
     */
    private $firstDistinguishingParameterValue;

    /**
     * @var string|null
     */
    private $firstDistinguishingParameterName;

    /**
     * @var string|null
     */
    private $secondDistinguishingParameterValue;

    /**
     * @var string|null
     */
    private $secondDistinguishingParameterName;

    /**
     * @param string|null $firstDistinguishingParameterValue
     * @param string|null $secondDistinguishingParameterValue
     */
    public function __construct(?ProductParameterValue $firstDistinguishingParameterValue, ?ProductParameterValue $secondDistinguishingParameterValue)
    {
        if ($firstDistinguishingParameterValue !== null) {
            $this->firstDistinguishingParameterValue = $firstDistinguishingParameterValue->getValue()->getText();
            $this->firstDistinguishingParameterName = $firstDistinguishingParameterValue->getParameter()->getName();
        }
        if ($secondDistinguishingParameterValue !== null) {
            $this->secondDistinguishingParameterValue = $secondDistinguishingParameterValue->getValue()->getText();
            $this->secondDistinguishingParameterName = $secondDistinguishingParameterValue->getParameter()->getName();
        }
    }

    /**
     * @return string|null
     */
    public function getFirstDistinguishingParameterValue(): ?string
    {
        return $this->firstDistinguishingParameterValue;
    }

    /**
     * @return string|null
     */
    public function getSecondDistinguishingParameterValue(): ?string
    {
        return $this->secondDistinguishingParameterValue;
    }

    /**
     * @return string|null
     */
    public function getFirstDistinguishingParameterName(): ?string
    {
        return $this->firstDistinguishingParameterName;
    }

    /**
     * @return string|null
     */
    public function getSecondDistinguishingParameterName(): ?string
    {
        return $this->secondDistinguishingParameterName;
    }
}
