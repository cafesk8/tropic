<?php

namespace Shopsys\ShopBundle\Controller\Front;

use Shopsys\FrameworkBundle\Model\Advert\AdvertFacade;

class AdvertController extends FrontBaseController
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Advert\AdvertFacade
     */
    private $advertFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Advert\AdvertFacade $advertFacade
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
            'fourthSquare' => $this->advertFacade->findRandomAdvertByPositionOnCurrentDomain('fourthSquare'),
            'fifthRectangle' => $this->advertFacade->findRandomAdvertByPositionOnCurrentDomain('fifthRectangle'),
        ]);
    }
}
