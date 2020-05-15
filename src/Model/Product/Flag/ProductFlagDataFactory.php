<?php

declare(strict_types=1);

namespace App\Model\Product\Flag;

use App\Model\Product\Product;
use DateTime;

class ProductFlagDataFactory
{
    /**
     * @param \App\Model\Product\Product $product
     * @param \App\Model\Product\Flag\Flag $flag
     * @param \DateTime|null $activeFrom
     * @param \DateTime|null $activeTo
     * @return \App\Model\Product\Flag\ProductFlagData
     */
    public function create(Product $product, Flag $flag, ?DateTime $activeFrom = null, ?DateTime $activeTo = null): ProductFlagData
    {
        $productFlagData = new ProductFlagData();
        $productFlagData->product = $product;
        $productFlagData->flag = $flag;
        $productFlagData->activeFrom = $activeFrom;
        $productFlagData->activeTo = $activeTo;

        return $productFlagData;
    }
}
