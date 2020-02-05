<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Front;

use Shopsys\FrameworkBundle\Model\Category\Category;
use Shopsys\ReadModelBundle\Product\Listed\ListedProductViewFacadeInterface;

class BestsellingProductController extends FrontBaseController
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\View\ListedProductViewElasticFacade
     */
    private $listedProductViewFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Product\View\ListedProductViewElasticFacade $listedProductViewFacade
     */
    public function __construct(ListedProductViewFacadeInterface $listedProductViewFacade)
    {
        $this->listedProductViewFacade = $listedProductViewFacade;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Category\Category $category
     */
    public function listAction(Category $category)
    {
        $bestsellingProducts = $this->listedProductViewFacade->getAllOfferedBestsellingProducts($category);

        return $this->render('@ShopsysShop/Front/Content/Product/bestsellingProductsList.html.twig', [
            'productViews' => $bestsellingProducts,
        ]);
    }
}
