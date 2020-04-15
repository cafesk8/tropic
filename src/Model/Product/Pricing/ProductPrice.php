<?php

declare(strict_types=1);

namespace App\Model\Product\Pricing;

use App\Model\Product\Pricing\Exception\PriceLessThanZeroException;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice as BaseProductPrice;

class ProductPrice extends BaseProductPrice
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Price
     */
    private $defaultProductPrice;

    /**
     * @var int
     */
    private $productId;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Price|null
     */
    private $standardPrice;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $price
     * @param mixed $priceFrom
     * @param int $productId
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $defaultProductPrice
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $standardPrice
     */
    public function __construct(
        Price $price,
        $priceFrom,
        int $productId,
        ?Price $defaultProductPrice = null,
        ?Price $standardPrice = null
    ) {
        parent::__construct($price, $priceFrom);
        $this->defaultProductPrice = $defaultProductPrice ?? Price::zero();
        $this->productId = $productId;
        $this->standardPrice = $standardPrice;
    }

    /**
     * @return bool
     */
    public function hasHigherStandardPrice(): bool
    {
        return $this->standardPrice !== null && $this->priceWithVat->isLessThan($this->standardPrice->priceWithVat);
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Money\Money
     */
    public function getStandardPriceDifference(): Money
    {
        if ($this->standardPrice->priceWithVat->subtract($this->priceWithVat)->isNegative()) {
            throw new PriceLessThanZeroException();
        }

        return $this->standardPrice->priceWithVat->subtract($this->priceWithVat);
    }

    /**
     * @return int
     */
    public function getPricePercentageDifference(): int
    {
        $percents = $this->getStandardPriceDifference()->divide($this->standardPrice->priceWithVat->getAmount(), 6)->multiply(100)->getAmount();

        return (int)round($percents);
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price
     */
    public function defaultProductPrice(): Price
    {
        return $this->defaultProductPrice;
    }

    /**
     * @return int
     */
    public function getProductId(): int
    {
        return $this->productId;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price|null
     */
    public function getStandardPrice(): ?Price
    {
        return $this->standardPrice;
    }
}
