<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Model\Product\View\ListedProductViewElasticFacade;
use Symfony\Component\HttpFoundation\Request;

class LastVisitedProductsController extends FrontBaseController
{
    public const MAX_VISITED_PRODUCT_COUNT = 12;

    /**
     * @var \App\Model\Product\View\ListedProductViewElasticFacade
     */
    private $listedProductViewElasticFacade;

    /**
     * @param \App\Model\Product\View\ListedProductViewElasticFacade $listedProductViewElasticFacade
     */
    public function __construct(ListedProductViewElasticFacade $listedProductViewElasticFacade)
    {
        $this->listedProductViewElasticFacade = $listedProductViewElasticFacade;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction(Request $request)
    {
        $productViews = $this->listedProductViewElasticFacade->getProductsFromCookieSortedByNewest(
            $request->cookies,
            self::MAX_VISITED_PRODUCT_COUNT
        );

        return $this->render('Front/Content/LastVisitedProducts/list.html.twig', [
            'productViews' => $productViews,
        ]);
    }
}
