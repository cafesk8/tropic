<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Model\Product\View\ListedProductViewElasticFacade;
use Shopsys\ReadModelBundle\Product\Listed\ListedProductViewFacadeInterface;
use Symfony\Component\HttpFoundation\Response;

class BestsellerController extends FrontBaseController
{
    private ListedProductViewElasticFacade $listedProductViewFacade;

    /**
     * @param \App\Model\Product\View\ListedProductViewElasticFacade $listedProductViewFacade
     */
    public function __construct(ListedProductViewFacadeInterface $listedProductViewFacade)
    {
        $this->listedProductViewFacade = $listedProductViewFacade;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(): Response
    {
        $bestsellers = $this->listedProductViewFacade->getAllBestsellers();

        return $this->render('Front/Content/Product/bestsellersList.html.twig', [
            'bestsellers' => $bestsellers,
        ]);
    }
}
