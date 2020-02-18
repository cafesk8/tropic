<?php

declare(strict_types=1);

namespace App\Model\Advert;

use App\Model\Advert\Product\AdvertProduct;
use App\Model\Advert\Product\AdvertProductRepository;
use Shopsys\FrameworkBundle\Model\Advert\Advert as BaseAdvert;
use Shopsys\FrameworkBundle\Model\Advert\AdvertData as BaseAdvertData;
use Shopsys\FrameworkBundle\Model\Advert\AdvertDataFactory as BaseAdvertDataFactory;

/**
 * @property \App\Component\Image\ImageFacade|null $imageFacade
 * @method setImageFacade(\App\Component\Image\ImageFacade $imageFacade)
 */
class AdvertDataFactory extends BaseAdvertDataFactory
{
    /**
     * @var \App\Model\Advert\Product\AdvertProductRepository
     */
    private $advertProductRepository;

    /**
     * @param \App\Model\Advert\Product\AdvertProductRepository $advertProductRepository
     */
    public function __construct(AdvertProductRepository $advertProductRepository)
    {
        $this->advertProductRepository = $advertProductRepository;
    }

    /**
     * @return \App\Model\Advert\AdvertData
     */
    public function create(): BaseAdvertData
    {
        return new AdvertData();
    }

    /**
     * @param \App\Model\Advert\Advert $advert
     * @return \App\Model\Advert\AdvertData
     */
    public function createFromAdvert(BaseAdvert $advert): BaseAdvertData
    {
        $advertData = new AdvertData();
        $this->fillFromAdvert($advertData, $advert);

        return $advertData;
    }

    /**
     * @param \App\Model\Advert\AdvertData $advertData
     * @param \App\Model\Advert\Advert $advert
     */
    protected function fillFromAdvert(BaseAdvertData $advertData, BaseAdvert $advert): void
    {
        parent::fillFromAdvert($advertData, $advert);

        $advertData->smallTitle = $advert->getSmallTitle();
        $advertData->bigTitle = $advert->getBigTitle();
        $advertData->productTitle = $advert->getProductTitle();
        $advertData->products = array_map(function (AdvertProduct $advertProduct) {
            return $advertProduct->getProduct();
        }, $this->advertProductRepository->getAdvertProductsByAdvert($advert));
    }
}
