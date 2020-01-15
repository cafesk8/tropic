<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Brand;

use Shopsys\FrameworkBundle\Model\Product\Brand\BrandFacade as BaseBrandFacade;

/**
 * @property \Shopsys\ShopBundle\Model\Product\Brand\BrandRepository $brandRepository
 */
class BrandFacade extends BaseBrandFacade
{
    /**
     * @inheritDoc
     */
    public function deleteById($brandId)
    {
        /** @var \Shopsys\ShopBundle\Model\Product\Brand\Brand $brand */
        $brand = $this->brandRepository->getById($brandId);
        $brand->checkForDelete();

        parent::deleteById($brandId);
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Product\Brand\Brand
     */
    public function getMainBushmanBrand(): Brand
    {
        return $this->brandRepository->getMainBushmanBrand();
    }
}
