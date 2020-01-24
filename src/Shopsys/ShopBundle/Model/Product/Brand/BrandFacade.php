<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Brand;

use Shopsys\FrameworkBundle\Model\Product\Brand\BrandFacade as BaseBrandFacade;

/**
 * @property \Shopsys\ShopBundle\Model\Product\Brand\BrandRepository $brandRepository
 * @property \Shopsys\ShopBundle\Component\Image\ImageFacade $imageFacade
 * @property \Shopsys\ShopBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
 * @method __construct(\Doctrine\ORM\EntityManagerInterface $em, \Shopsys\ShopBundle\Model\Product\Brand\BrandRepository $brandRepository, \Shopsys\ShopBundle\Component\Image\ImageFacade $imageFacade, \Shopsys\ShopBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade, \Shopsys\FrameworkBundle\Component\Domain\Domain $domain, \Shopsys\FrameworkBundle\Model\Product\Brand\BrandFactoryInterface $brandFactory)
 * @method \Shopsys\ShopBundle\Model\Product\Brand\Brand getById(int $brandId)
 * @method \Shopsys\ShopBundle\Model\Product\Brand\Brand create(\Shopsys\ShopBundle\Model\Product\Brand\BrandData $brandData)
 * @method \Shopsys\ShopBundle\Model\Product\Brand\Brand edit(int $brandId, \Shopsys\ShopBundle\Model\Product\Brand\BrandData $brandData)
 * @method \Shopsys\ShopBundle\Model\Product\Brand\Brand[] getAll()
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
