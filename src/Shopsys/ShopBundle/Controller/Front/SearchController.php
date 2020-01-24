<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Front;

use Shopsys\FrameworkBundle\Model\Category\CategoryFacade;
use Shopsys\FrameworkBundle\Model\Product\ProductOnCurrentDomainFacadeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchController extends FrontBaseController
{
    public const AUTOCOMPLETE_CATEGORY_LIMIT = 3;
    public const AUTOCOMPLETE_PRODUCT_LIMIT = 5;

    /**
     * @var \Shopsys\ShopBundle\Model\Category\CategoryFacade
     */
    private $categoryFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductOnCurrentDomainElasticFacade
     */
    private $productOnCurrentDomainFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Category\CategoryFacade $categoryFacade
     * @param \Shopsys\ShopBundle\Model\Product\ProductOnCurrentDomainElasticFacade $productOnCurrentDomainFacade
     */
    public function __construct(
        CategoryFacade $categoryFacade,
        ProductOnCurrentDomainFacadeInterface $productOnCurrentDomainFacade
    ) {
        $this->categoryFacade = $categoryFacade;
        $this->productOnCurrentDomainFacade = $productOnCurrentDomainFacade;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function autocompleteAction(Request $request)
    {
        $searchText = $request->get('searchText');
        $searchUrl = $this->generateUrl('front_product_search', [ProductController::SEARCH_TEXT_PARAMETER => $searchText]);

        $categoriesPaginationResult = $this->categoryFacade
            ->getSearchAutocompleteCategories($searchText, self::AUTOCOMPLETE_CATEGORY_LIMIT);

        $productsPaginationResult = $this->productOnCurrentDomainFacade
            ->getSearchAutocompleteProducts($searchText, self::AUTOCOMPLETE_PRODUCT_LIMIT);

        return $this->render('@ShopsysShop/Front/Content/Search/autocomplete.html.twig', [
            'searchUrl' => $searchUrl,
            'categoriesPaginationResult' => $categoriesPaginationResult,
            'productsPaginationResult' => $productsPaginationResult,
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param bool $withButton
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function boxAction(Request $request, bool $withButton = false): Response
    {
        $searchText = $request->query->get(ProductController::SEARCH_TEXT_PARAMETER);

        return $this->render('@ShopsysShop/Front/Content/Search/searchBox.html.twig', [
            'searchText' => $searchText,
            'SEARCH_TEXT_PARAMETER' => ProductController::SEARCH_TEXT_PARAMETER,
            'renderButton' => $withButton,
        ]);
    }
}
