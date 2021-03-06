<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Model\Product\BestsellingProduct\BestsellingProductFacade;
use Shopsys\FrameworkBundle\Model\Category\Category;
use Shopsys\ReadModelBundle\Product\Listed\ListedProductViewFacadeInterface;

class BestsellingProductController extends FrontBaseController
{
    public const TYPE_VERTICAL = 'vertical';

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
     * @param string|null $type
     * @param string|null $routeName
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(Category $category, ?string $type = null, ?string $routeName = null)
    {
        $bestsellingProducts = $this->listedProductViewFacade->getAllOfferedBestsellingProducts($category, $routeName);

        $templateName = 'Front/Content/Product/bestsellingProductsList.html.twig';
        $viewParameters = ['productViews' => $bestsellingProducts];
        if ($type === self::TYPE_VERTICAL) {
            $templateName = 'Front/Content/Product/bestsellingProductsListVertical.html.twig';
            $viewParameters['maxShownProducts'] = BestsellingProductFacade::MAX_SHOW_RESULTS;
        }

        return $this->render($templateName, $viewParameters);
    }
}
