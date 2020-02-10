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
    public function deleteById($brandId): void
    {
        /** @var \Shopsys\ShopBundle\Model\Product\Brand\Brand $brand */
        $brand = $this->brandRepository->getById($brandId);
        $brand->checkForDelete();

        parent::deleteById($brandId);
    }

    /**
     * @return array
     */
    public function getAllNamesIndexedById(): array
    {
        $brands = $this->getAll();
        $brandsArray = [];

        foreach ($brands as $brand) {
            $brandsArray[$brand->getId()] = $brand->getName();
        }

        return $brandsArray;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Product\Brand\Brand
     */
    public function getMainShopsysBrand(): Brand
    {
        return $this->brandRepository->getMainShopsysBrand();
    }
}
