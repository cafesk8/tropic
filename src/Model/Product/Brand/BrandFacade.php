<?php

declare(strict_types=1);

namespace App\Model\Product\Brand;

use Shopsys\FrameworkBundle\Model\Product\Brand\BrandFacade as BaseBrandFacade;

/**
 * @property \App\Component\Image\ImageFacade $imageFacade
 * @property \App\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
 * @method __construct(\Doctrine\ORM\EntityManagerInterface $em, \App\Model\Product\Brand\BrandRepository $brandRepository, \App\Component\Image\ImageFacade $imageFacade, \App\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade, \Shopsys\FrameworkBundle\Component\Domain\Domain $domain, \Shopsys\FrameworkBundle\Model\Product\Brand\BrandFactoryInterface $brandFactory, \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher)
 * @method \App\Model\Product\Brand\Brand getById(int $brandId)
 * @method \App\Model\Product\Brand\Brand create(\App\Model\Product\Brand\BrandData $brandData)
 * @method \App\Model\Product\Brand\Brand edit(int $brandId, \App\Model\Product\Brand\BrandData $brandData)
 * @method \App\Model\Product\Brand\Brand[] getAll()
 * @method dispatchBrandEvent(\App\Model\Product\Brand\Brand $brand, string $eventType)
 */
class BrandFacade extends BaseBrandFacade
{
    /**
     * @var \App\Model\Product\Brand\BrandRepository
     */
    protected $brandRepository;

    /**
     * @param string $name
     * @return \App\Model\Product\Brand\Brand
     */
    public function getByName(string $name): Brand
    {
        return $this->brandRepository->getByName($name);
    }
}
