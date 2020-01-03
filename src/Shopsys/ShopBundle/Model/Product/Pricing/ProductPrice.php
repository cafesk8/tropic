<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Pricing;

use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice as BaseProductPrice;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\ShopBundle\Model\Product\Pricing\Exception\PriceLessThanZeroException;

class ProductPrice extends BaseProductPrice
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Price
     */
    private $defaultProductPrice;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup|null
     */
    private $activePricingGroup;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup|null
     */
    private $defaultPricingGroup;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Product|null
     */
    private $product;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $price
     * @param mixed $priceFrom
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup|null $activePricingGroup
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup|null $defaultPricingGroup
     * @param \Shopsys\FrameworkBundle\Model\Product\Product|null $product
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $defaultProductPrice
     */
    public function __construct(
        Price $price,
        $priceFrom,
        ?PricingGroup $activePricingGroup,
        ?PricingGroup $defaultPricingGroup,
        ?Product $product,
        ?Price $defaultProductPrice = null
    ) {
        parent::__construct($price, $priceFrom);
        $this->defaultProductPrice = $defaultProductPrice ?? Price::zero();
        $this->activePricingGroup = $activePricingGroup;
        $this->defaultPricingGroup = $defaultPricingGroup;
        $this->product = $product;
    }

    /**
     * Less price is considered as less
     *      because if user's pricing group is default pricing group
     *      or if it has action price (ProductDomain::actionPrice)
     *
     * @return bool
     */
    public function isActionPrice(): bool
    {
        if ($this->priceWithVat->isLessThan($this->defaultProductPrice->priceWithVat)
            && (
                $this->product === null
                || $this->activePricingGroup === null
                || $this->defaultPricingGroup === null
                || $this->product->getActionPrice($this->activePricingGroup->getDomainId()) !== null
                || $this->activePricingGroup === $this->defaultPricingGroup
            )
        ) {
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
