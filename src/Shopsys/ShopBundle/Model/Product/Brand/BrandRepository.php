<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Brand;

use Shopsys\FrameworkBundle\Model\Product\Brand\BrandRepository as BaseBrandRepository;
use Shopsys\FrameworkBundle\Model\Product\Brand\Exception\BrandNotFoundException;

class BrandRepository extends BaseBrandRepository
{
    /**
     * @return \Shopsys\ShopBundle\Model\Product\Brand\Brand
     */
    public function getMainBushmanBrand(): Brand
    {
        $brand = $this->getBrandRepository()->findOneBy(['type' => Brand::TYPE_MAIN_BUSHMAN]);
        if ($brand === null) {
            throw new BrandNotFoundException('Main brand not found');
        }

        return $brand;
    }
}
