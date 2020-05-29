<?php

declare(strict_types=1);

namespace App\Model\Product\Flag;

use DateTime;

class ProductFlagDataFactory
{
    /**
     * @param \App\Model\Product\Flag\Flag $flag
     * @param \DateTime|null $activeFrom
     * @param \DateTime|null $activeTo
     * @return \App\Model\Product\Flag\ProductFlagData
     */
    public function create(Flag $flag, ?DateTime $activeFrom = null, ?DateTime $activeTo = null): ProductFlagData
    {
        $productFlagData = $this->createEmpty();
        $productFlagData->flag = $flag;
        $productFlagData->activeFrom = $activeFrom;
        $productFlagData->activeTo = $activeTo;

        return $productFlagData;
    }

    /**
     * @param \App\Model\Product\Flag\ProductFlag $productFlag
     * @return \App\Model\Product\Flag\ProductFlagData
     */
    public function createFromProductFlag(ProductFlag $productFlag): ProductFlagData
    {
        $productFlagData = $this->createEmpty();
        $productFlagData->flag = $productFlag->getFlag();
        $productFlagData->activeFrom = $productFlag->getActiveFrom();
        $productFlagData->activeTo = $productFlag->getActiveTo();

        return $productFlagData;
    }

    /**
     * @return \App\Model\Product\Flag\ProductFlagData
     */
    public function createEmpty(): ProductFlagData
    {
        return new ProductFlagData();
    }
}
