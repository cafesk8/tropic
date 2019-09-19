<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Pricing;

use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice as BaseProductPrice;
use Shopsys\ShopBundle\Model\Product\Pricing\Exception\PriceLessThanZeroException;

class ProductPrice extends BaseProductPrice
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Price
     */
    private $defaultProductPrice;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $price
     * @param mixed $priceFrom
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $defaultProductPrice
     */
    public function __construct(Price $price, $priceFrom, ?Price $defaultProductPrice = null)
    {
        parent::__construct($price, $priceFrom);
        $this->defaultProductPrice = $defaultProductPrice ?? Price::zero();
    }

    /**
     * @return bool
     */
    public function isActionPrice(): bool
    {
        if ($this->priceWithVat->isLessThan($this->defaultProductPrice->priceWithVat)) {
            return true;
        }

        return false;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Money\Money
     */
    public function getActionPriceDifference(): Money
    {
        if ($this->defaultProductPrice->priceWithVat->subtract($this->priceWithVat)->isNegative()) {
            throw new PriceLessThanZeroException();
        }

        return $this->defaultProductPrice->priceWithVat->subtract($this->priceWithVat);
    }

    /**
     * @return int
     */
    public function getPricePercentageDifference(): int
    {
        $percents = $this->getActionPriceDifference()->divide($this->defaultProductPrice->priceWithVat->getAmount(), 6)->multiply(100)->getAmount();

        return (int)$percents;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price
     */
    public function defaultProductPrice(): Price
    {
        return $this->defaultProductPrice;
    }
}
