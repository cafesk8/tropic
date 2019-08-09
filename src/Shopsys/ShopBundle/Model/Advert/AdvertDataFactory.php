<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Advert;

use Shopsys\FrameworkBundle\Model\Advert\Advert as BaseAdvert;
use Shopsys\FrameworkBundle\Model\Advert\AdvertData as BaseAdvertData;
use Shopsys\FrameworkBundle\Model\Advert\AdvertDataFactory as BaseAdvertDataFactory;

class AdvertDataFactory extends BaseAdvertDataFactory
{
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

        $advertData->smallTitle = $advert->getSmallTitle();
        $advertData->bigTitle = $advert->getBigTitle();
        $advertData->productTitle = $advert->getProductTitle();

        return $advertData;
    }
}
