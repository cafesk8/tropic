<?php

declare(strict_types=1);

namespace App\Controller\Front;

use Shopsys\FrameworkBundle\Model\Category\Category;
use Shopsys\ReadModelBundle\Product\Listed\ListedProductViewFacadeInterface;

class BestsellingProductController extends FrontBaseController
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

    /**
     * @param \App\Model\Category\Category $category
     */
    public function listAction(Category $category)
    {
        $bestsellingProducts = $this->listedProductViewFacade->getAllOfferedBestsellingProducts($category);

        return $this->render('Front/Content/Product/bestsellingProductsList.html.twig', [
            'productViews' => $bestsellingProducts,
        ]);
    }
}
