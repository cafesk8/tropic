<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Advert;

use Shopsys\FrameworkBundle\Model\Advert\Advert as BaseAdvert;
use Shopsys\FrameworkBundle\Model\Advert\AdvertData as BaseAdvertData;
use Shopsys\FrameworkBundle\Model\Advert\AdvertDataFactory as BaseAdvertDataFactory;
use Shopsys\ShopBundle\Model\Advert\Product\AdvertProduct;
use Shopsys\ShopBundle\Model\Advert\Product\AdvertProductRepository;

class AdvertDataFactory extends BaseAdvertDataFactory
{
    /**
     * @var \Shopsys\ShopBundle\Model\Advert\Product\AdvertProductRepository
     */
    private $advertProductRepository;

    /**
     * @param \Shopsys\ShopBundle\Model\Advert\Product\AdvertProductRepository $advertProductRepository
     */
    public function __construct(AdvertProductRepository $advertProductRepository)
    {
        $this->advertProductRepository = $advertProductRepository;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Advert\AdvertData
     */
    public function create(): BaseAdvertData
    {
        return new AdvertData();
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Advert\Advert $advert
     * @return \Shopsys\ShopBundle\Model\Advert\AdvertData
     */
    public function createFromAdvert(BaseAdvert $advert): BaseAdvertData
    {
        $advertData = new AdvertData();
        $this->fillFromAdvert($advertData, $advert);

        return $advertData;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Advert\AdvertData $advertData
     * @param \Shopsys\ShopBundle\Model\Advert\Advert $advert
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
