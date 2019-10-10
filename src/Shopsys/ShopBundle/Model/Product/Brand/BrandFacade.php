<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Brand;

use Shopsys\FrameworkBundle\Model\Product\Brand\BrandFacade as BaseBrandFacade;

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
}
