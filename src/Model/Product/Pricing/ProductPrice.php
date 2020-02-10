<?php

declare(strict_types=1);

namespace App\Model\Product\Pricing;

use App\Model\Product\Pricing\Exception\PriceLessThanZeroException;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice as BaseProductPrice;

class ProductPrice extends BaseProductPrice
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Price
     */
    private $defaultProductPrice;

    /**
     * @var \App\Model\Pricing\Group\PricingGroup|null
     */
    private $activePricingGroup;

    /**
     * @var \App\Model\Pricing\Group\PricingGroup|null
     */
    private $defaultPricingGroup;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    private $actionPriceForCurrentDomain;

    /**
     * @var int
     */
    private $productId;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $price
     * @param mixed $priceFrom
     * @param int $productId
     * @param \App\Model\Pricing\Group\PricingGroup|null $activePricingGroup
     * @param \App\Model\Pricing\Group\PricingGroup|null $defaultPricingGroup
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $actionPriceForCurrentDomain
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $defaultProductPrice
     */
    public function __construct(
        Price $price,
        $priceFrom,
        int $productId,
        ?PricingGroup $activePricingGroup,
        ?PricingGroup $defaultPricingGroup,
        ?Money $actionPriceForCurrentDomain,
        ?Price $defaultProductPrice = null
    ) {
        parent::__construct($price, $priceFrom);
        $this->defaultProductPrice = $defaultProductPrice ?? Price::zero();
        $this->activePricingGroup = $activePricingGroup;
        $this->defaultPricingGroup = $defaultPricingGroup;
        $this->actionPriceForCurrentDomain = $actionPriceForCurrentDomain;
        $this->productId = $productId;
    }

    /**
     * @return bool
     */
    public function isActionPriceByUsedForPromoCode(): bool
    {
        if ($this->isActionPrice()
            && (
                $this->activePricingGroup === null
                || $this->defaultPricingGroup === null
                || $this->actionPriceForCurrentDomain !== null
                || $this->activePricingGroup === $this->defaultPricingGroup
            )
        ) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isActionPrice(): bool
    {
        return $this->priceWithVat->isLessThan($this->defaultProductPrice->priceWithVat);
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

    /**
     * @return int
     */
    public function getProductId(): int
    {
        return $this->productId;
    }
}
