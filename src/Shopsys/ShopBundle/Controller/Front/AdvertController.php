<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Front;

use Shopsys\ShopBundle\Model\Advert\AdvertFacade;
use Shopsys\ShopBundle\Model\Product\ProductOnCurrentDomainElasticFacade;
use Symfony\Component\HttpFoundation\Response;

class AdvertController extends FrontBaseController
{
    private const LIMIT_FOR_PRODUCTS_TO_SHOW = 3;

    /**
     * @var \Shopsys\ShopBundle\Model\Advert\AdvertFacade
     */
    private $advertFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductOnCurrentDomainElasticFacade
     */
    private $productOnCurrentDomainFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Advert\AdvertFacade $advertFacade
     * @param \Shopsys\ShopBundle\Model\Product\ProductOnCurrentDomainElasticFacade $productOnCurrentDomainFacade
     */
    public function __construct(AdvertFacade $advertFacade, ProductOnCurrentDomainElasticFacade $productOnCurrentDomainFacade)
    {
        $this->advertFacade = $advertFacade;
        $this->productOnCurrentDomainFacade = $productOnCurrentDomainFacade;
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

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function bigBannerOnHomepageAction(): Response
    {
        /** @var \Shopsys\ShopBundle\Model\Advert\Advert $advert */
        $advert = $this->advertFacade->findRandomAdvertByPositionOnCurrentDomain('sixthRectangle');
        $advertProducts = $this->advertFacade->getAdvertProductsByAdvertAndLimit($advert, self::LIMIT_FOR_PRODUCTS_TO_SHOW);

        return $this->render('@ShopsysShop/Front/Content/Advert/bigBannerOnHomepage.html.twig', [
            'advert' => $advert,
            'advertProducts' => $advertProducts,
            'variantsIndexedByMainVariantId' => $this->productOnCurrentDomainFacade->getVariantsIndexedByMainVariantId($advertProducts),
        ]);
    }
}
