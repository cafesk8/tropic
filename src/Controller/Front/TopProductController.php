<?php

declare(strict_types=1);

namespace App\Controller\Front;

use Shopsys\ReadModelBundle\Product\Listed\ListedProductViewFacadeInterface;

class TopProductController extends FrontBaseController
{
    /**
     * @var \App\Model\Product\View\ListedProductViewElasticFacade
     */
    private $listedProductViewFacade;

    /**
     * @param \App\Model\Product\View\ListedProductViewElasticFacade $listedProductViewFacade
     */
    public function __construct(ListedProductViewFacadeInterface $listedProductViewFacade)
    {
        $this->listedProductViewFacade = $listedProductViewFacade;
    }

    public function listAction()
    {
        $topProducts = $this->listedProductViewFacade->getAllTop();

        return $this->render('Front/Content/Product/topProductsList.html.twig', [
            'topProducts' => $topProducts,
        ]);
    }
}
