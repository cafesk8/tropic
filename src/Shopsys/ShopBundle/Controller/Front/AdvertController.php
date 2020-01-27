<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Front;

use Shopsys\ShopBundle\Model\Advert\AdvertFacade;

class AdvertController extends FrontBaseController
{
    /**
     * @var \Shopsys\ShopBundle\Model\Advert\AdvertFacade
     */
    private $advertFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Advert\AdvertFacade $advertFacade
     */
    public function __construct(AdvertFacade $advertFacade)
    {
        $this->advertFacade = $advertFacade;
    }

    public function bannerAction()
    {
        return $this->render('@ShopsysShop/Front/Content/Advert/banners.html.twig', [
            'firstSquare' => $this->advertFacade->findRandomAdvertByPositionOnCurrentDomain('firstSquare'),
            'secondSquare' => $this->advertFacade->findRandomAdvertByPositionOnCurrentDomain('secondSquare'),
            'thirdSquare' => $this->advertFacade->findRandomAdvertByPositionOnCurrentDomain('thirdSquare'),
            'fourthRectangle' => $this->advertFacade->findRandomAdvertByPositionOnCurrentDomain('fourthRectangle'),
        ]);
    }
}
