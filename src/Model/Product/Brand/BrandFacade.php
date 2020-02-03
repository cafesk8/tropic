<?php

declare(strict_types=1);

namespace App\Model\Product\Brand;

use Shopsys\FrameworkBundle\Model\Product\Brand\BrandFacade as BaseBrandFacade;

/**
 * @property \App\Model\Product\Brand\BrandRepository $brandRepository
 * @property \App\Component\Image\ImageFacade $imageFacade
 * @property \App\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
 * @method __construct(\Doctrine\ORM\EntityManagerInterface $em, \App\Model\Product\Brand\BrandRepository $brandRepository, \App\Component\Image\ImageFacade $imageFacade, \App\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade, \Shopsys\FrameworkBundle\Component\Domain\Domain $domain, \Shopsys\FrameworkBundle\Model\Product\Brand\BrandFactoryInterface $brandFactory)
 * @method \App\Model\Product\Brand\Brand getById(int $brandId)
 * @method \App\Model\Product\Brand\Brand create(\App\Model\Product\Brand\BrandData $brandData)
 * @method \App\Model\Product\Brand\Brand edit(int $brandId, \App\Model\Product\Brand\BrandData $brandData)
 * @method \App\Model\Product\Brand\Brand[] getAll()
 */
class BrandFacade extends BaseBrandFacade
{
    /**
     * @inheritDoc
     */
    public function deleteById($brandId): void
    {
        /** @var \App\Model\Product\Brand\Brand $brand */
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
     * @return \App\Model\Product\Brand\Brand
     */
    public function getMainShopsysBrand(): Brand
    {
        return $this->brandRepository->getMainShopsysBrand();
    }
}
