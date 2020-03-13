<?php

declare(strict_types=1);

namespace App\Model\Product\Gift;

use Shopsys\FrameworkBundle\Component\Money\Money;

class ProductGiftPriceCalculation
{
    /**
     * @return \Shopsys\FrameworkBundle\Component\Money\Money
     */
    public function getGiftPrice(): Money
    {
        return Money::create(0);
    }
}
