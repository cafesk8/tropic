<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Category\CategoryFacade;
use Shopsys\FrameworkBundle\Model\Product\ProductOnCurrentDomainFacadeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchController extends FrontBaseController
{
    public const AUTOCOMPLETE_CATEGORY_LIMIT = 3;
    public const AUTOCOMPLETE_PRODUCT_LIMIT = 4;
    public const AUTOCOMPLETE_SET_LIMIT = 1;

    /**
     * @var \App\Model\Category\CategoryFacade
     */
    private $categoryFacade;

    /**
     * @var \App\Model\Product\ProductOnCurrentDomainElasticFacade
     */
    private $productOnCurrentDomainFacade;

    /**
     * @param \App\Model\Category\CategoryFacade $categoryFacade
     * @param \App\Model\Product\ProductOnCurrentDomainElasticFacade $productOnCurrentDomainFacade
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

        $setsPaginationResult = $this->productOnCurrentDomainFacade
            ->getSearchAutocompleteProducts($searchText, self::AUTOCOMPLETE_SET_LIMIT, Product::POHODA_PRODUCT_TYPE_ID_PRODUCT_SET, false);

        return $this->render('Front/Content/Search/autocomplete.html.twig', [
            'searchUrl' => $searchUrl,
            'categoriesPaginationResult' => $categoriesPaginationResult,
            'productsPaginationResult' => $productsPaginationResult,
            'setsPaginationResult' => $setsPaginationResult,
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function boxAction(Request $request): Response
    {
        $searchText = $request->query->get(ProductController::SEARCH_TEXT_PARAMETER);

        return $this->render('Front/Content/Search/searchBox.html.twig', [
            'searchText' => $searchText,
            'SEARCH_TEXT_PARAMETER' => ProductController::SEARCH_TEXT_PARAMETER,
        ]);
    }
}
