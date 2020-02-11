<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Model\Advert\Advert;
use App\Model\Advert\AdvertFacade;
use Symfony\Component\HttpFoundation\Response;

class AdvertController extends FrontBaseController
{
    /**
     * @var \App\Model\Advert\AdvertFacade
     */
    private $advertFacade;

    /**
     * @var \App\Model\Product\ProductOnCurrentDomainElasticFacade
     */
    private $productOnCurrentDomainFacade;

    /**
     * @param \App\Model\Advert\AdvertFacade $advertFacade
     */
    public function __construct(AdvertFacade $advertFacade)
    {
        $this->advertFacade = $advertFacade;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function bannerAction()
    {
        return $this->render('Front/Content/Advert/banners.html.twig', [
            'firstSquare' => $this->advertFacade->findRandomAdvertByPositionOnCurrentDomain('firstSquare'),
            'secondSquare' => $this->advertFacade->findRandomAdvertByPositionOnCurrentDomain('secondSquare'),
            'thirdSquare' => $this->advertFacade->findRandomAdvertByPositionOnCurrentDomain('thirdSquare'),
            'fourthRectangle' => $this->advertFacade->findRandomAdvertByPositionOnCurrentDomain('fourthRectangle'),
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function bigBannerOnHomepageAction(): Response
    {
        /** @var \App\Model\Advert\Advert $advert */
        $advert = $this->advertFacade->findRandomAdvertByPositionOnCurrentDomain('sixthRectangle');
        $advertProducts = $this->advertFacade->getAdvertProductsByAdvert($advert);

        return $this->render('Front/Content/Advert/bigBannerOnHomepage.html.twig', [
            'advert' => $advert,
            'advertProducts' => $advertProducts,
            'variantsIndexedByMainVariantId' => $this->productOnCurrentDomainFacade->getVariantsIndexedByMainVariantId($advertProducts),
        ]);
    }

    /**
     * @param \App\Model\Advert\Advert|null $advert
     * @param string|null $sizeInfoName
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function boxAction(?Advert $advert, ?string $sizeInfoName = null)
    {
        return $this->render('Front/Content/Advert/box.html.twig', [
            'advert' => $advert,
            'sizeInfo' => $sizeInfoName,
        ]);
    }
}
