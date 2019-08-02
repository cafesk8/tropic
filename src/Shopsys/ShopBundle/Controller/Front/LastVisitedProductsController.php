<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Front;

use Shopsys\ShopBundle\Model\Product\LastVisitedProducts\LastVisitedProductsFacade;
use Symfony\Component\HttpFoundation\Request;

class LastVisitedProductsController extends FrontBaseController
{
    public const MAX_VISITED_PRODUCT_COUNT = 12;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\LastVisitedProducts\LastVisitedProductsFacade
     */
    private $lastVisitedProductFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Product\LastVisitedProducts\LastVisitedProductsFacade $lastVisitedProductFacade
     */
    public function __construct(LastVisitedProductsFacade $lastVisitedProductFacade)
    {
        $this->lastVisitedProductFacade = $lastVisitedProductFacade;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction(Request $request)
    {
        $products = $this->lastVisitedProductFacade->getProductsFromCookieSortedByNewest(
            $request->cookies,
            self::MAX_VISITED_PRODUCT_COUNT
        );

        return $this->render('@ShopsysShop/Front/Content/LastVisitedProducts/list.html.twig', [
            'products' => $products,
        ]);
    }
}
