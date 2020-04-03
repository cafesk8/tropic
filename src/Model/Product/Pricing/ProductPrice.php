<?php

declare(strict_types=1);

namespace App\Model\Product\Pricing;

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
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $price
     * @param mixed $priceFrom
     * @param int $productId
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $defaultProductPrice
     */
    public function __construct(
        Price $price,
        $priceFrom,
        int $productId,
        ?Price $defaultProductPrice = null
    ) {
        parent::__construct($price, $priceFrom);
        $this->defaultProductPrice = $defaultProductPrice ?? Price::zero();
        $this->productId = $productId;
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
