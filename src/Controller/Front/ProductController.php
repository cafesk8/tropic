<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Component\DiscountExclusion\DiscountExclusionFacade;
use App\Form\Front\Product\ProductFilterFormType;
use App\Model\Blog\Article\BlogArticleFacade;
use App\Model\Category\Category;
use App\Model\Category\CategoryBlogArticle\CategoryBlogArticleFacade;
use App\Model\Gtm\GtmFacade;
use App\Model\Heureka\HeurekaReviewFacade;
use App\Model\Pricing\Group\PricingGroupFacade;
use App\Model\Product\Brand\Brand;
use App\Model\Product\Flag\FlagFacade;
use App\Model\Product\Product;
use App\Model\Product\ProductFacade;
use App\Model\Product\View\ListedProductViewElasticFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Paginator\PaginationResult;
use Shopsys\FrameworkBundle\Model\Category\Category as BaseCategory;
use Shopsys\FrameworkBundle\Model\Category\CategoryFacade;
use Shopsys\FrameworkBundle\Model\Module\ModuleFacade;
use Shopsys\FrameworkBundle\Model\Module\ModuleList;
use Shopsys\FrameworkBundle\Model\Product\Brand\BrandFacade;
use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfigFactory;
use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterData;
use Shopsys\FrameworkBundle\Model\Product\Listing\ProductListOrderingModeForBrandFacade;
use Shopsys\FrameworkBundle\Model\Product\Listing\ProductListOrderingModeForListFacade;
use Shopsys\FrameworkBundle\Model\Product\Listing\ProductListOrderingModeForSearchFacade;
use Shopsys\FrameworkBundle\Model\Product\ProductOnCurrentDomainFacadeInterface;
use Shopsys\FrameworkBundle\Twig\RequestExtension;
use Shopsys\ReadModelBundle\Product\Listed\ListedProductViewFacadeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends FrontBaseController
{
    public const SEARCH_TEXT_PARAMETER = 'q';
    public const PAGE_QUERY_PARAMETER = 'page';
    public const PAGE_SETS_QUERY_PARAMETER = 'pageSets';
    public const PRODUCTS_PER_PAGE = 24;
    public const VISIBLE_FILTER_CHOICES_LIMIT = 4;
    private const PRODUCT_BLOG_ARTICLES_LIMIT = 2;
    private const LIST_BLOG_ARTICLES_LIMIT = 1;
    private const PRE_LIST_BLOG_ARTICLES_LIMIT = 2;

    /**
     * @var \App\Model\Product\Brand\BrandFacade
     */
    protected $brandFacade;

    /**
     * @var \App\Model\Product\Filter\ProductFilterConfigFactory
     */
    private $productFilterConfigFactory;

    /**
     * @var \App\Model\Category\CategoryFacade
     */
    private $categoryFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \App\Model\Product\ProductOnCurrentDomainElasticFacade
     */
    private $productOnCurrentDomainFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Twig\RequestExtension
     */
    private $requestExtension;

    /**
     * @var \App\Model\Product\Listing\ProductListOrderingModeForListFacade
     */
    private $productListOrderingModeForListFacade;

    /**
     * @var \App\Model\Product\Listing\ProductListOrderingModeForBrandFacade
     */
    private $productListOrderingModeForBrandFacade;

    /**
     * @var \App\Model\Product\Listing\ProductListOrderingModeForSearchFacade
     */
    private $productListOrderingModeForSearchFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Module\ModuleFacade
     */
    private $moduleFacade;

    /**
     * @var \App\Model\Blog\Article\BlogArticleFacade
     */
    private $blogArticleFacade;

    /**
     * @var \App\Model\Category\CategoryBlogArticle\CategoryBlogArticleFacade
     */
    private $categoryBlogArticleFacade;

    /**
     * @var \App\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \App\Model\Gtm\GtmFacade
     */
    private $gtmFacade;

    /**
     * @var \App\Model\Product\View\ListedProductViewElasticFacade
     */
    private $listedProductViewFacade;

    /**
     * @var \App\Component\DiscountExclusion\DiscountExclusionFacade
     */
    private $discountExclusionFacade;

    /**
     * @var \App\Model\Product\View\ListedProductViewElasticFacade
     */
    private $listedProductViewElasticFacade;

    /**
     * @var \App\Model\Pricing\Group\PricingGroupFacade
     */
    private $pricingGroupFacade;

    /**
     * @var \App\Model\Product\Flag\FlagFacade
     */
    private $flagFacade;

    /**
     * @var \App\Model\Heureka\HeurekaReviewFacade
     */
    private $heurekaReviewFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Twig\RequestExtension $requestExtension
     * @param \App\Model\Category\CategoryFacade $categoryFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Product\ProductOnCurrentDomainElasticFacade $productOnCurrentDomainFacade
     * @param \App\Model\Product\Filter\ProductFilterConfigFactory $productFilterConfigFactory
     * @param \App\Model\Product\Listing\ProductListOrderingModeForListFacade $productListOrderingModeForListFacade
     * @param \App\Model\Product\Listing\ProductListOrderingModeForBrandFacade $productListOrderingModeForBrandFacade
     * @param \App\Model\Product\Listing\ProductListOrderingModeForSearchFacade $productListOrderingModeForSearchFacade
     * @param \Shopsys\FrameworkBundle\Model\Module\ModuleFacade $moduleFacade
     * @param \App\Model\Blog\Article\BlogArticleFacade $blogArticleFacade
     * @param \App\Model\Category\CategoryBlogArticle\CategoryBlogArticleFacade $categoryBlogArticleFacade
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Model\Gtm\GtmFacade $gtmFacade
     * @param \App\Model\Product\View\ListedProductViewElasticFacade $listedProductViewFacade
     * @param \App\Model\Product\Brand\BrandFacade $brandFacade
     * @param \App\Component\DiscountExclusion\DiscountExclusionFacade $discountExclusionFacade
     * @param \App\Model\Product\View\ListedProductViewElasticFacade $listedProductViewElasticFacade
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \App\Model\Product\Flag\FlagFacade $flagFacade
     * @param \App\Model\Heureka\HeurekaReviewFacade $heurekaReviewFacade
     */
    public function __construct(
        RequestExtension $requestExtension,
        CategoryFacade $categoryFacade,
        Domain $domain,
        ProductOnCurrentDomainFacadeInterface $productOnCurrentDomainFacade,
        ProductFilterConfigFactory $productFilterConfigFactory,
        ProductListOrderingModeForListFacade $productListOrderingModeForListFacade,
        ProductListOrderingModeForBrandFacade $productListOrderingModeForBrandFacade,
        ProductListOrderingModeForSearchFacade $productListOrderingModeForSearchFacade,
        ModuleFacade $moduleFacade,
        BlogArticleFacade $blogArticleFacade,
        CategoryBlogArticleFacade $categoryBlogArticleFacade,
        ProductFacade $productFacade,
        GtmFacade $gtmFacade,
        ListedProductViewFacadeInterface $listedProductViewFacade,
        BrandFacade $brandFacade,
        DiscountExclusionFacade $discountExclusionFacade,
        ListedProductViewElasticFacade $listedProductViewElasticFacade,
        PricingGroupFacade $pricingGroupFacade,
        FlagFacade $flagFacade,
        HeurekaReviewFacade $heurekaReviewFacade
    ) {
        $this->requestExtension = $requestExtension;
        $this->categoryFacade = $categoryFacade;
        $this->domain = $domain;
        $this->productOnCurrentDomainFacade = $productOnCurrentDomainFacade;
        $this->productFilterConfigFactory = $productFilterConfigFactory;
        $this->productListOrderingModeForListFacade = $productListOrderingModeForListFacade;
        $this->productListOrderingModeForBrandFacade = $productListOrderingModeForBrandFacade;
        $this->productListOrderingModeForSearchFacade = $productListOrderingModeForSearchFacade;
        $this->moduleFacade = $moduleFacade;
        $this->blogArticleFacade = $blogArticleFacade;
        $this->categoryBlogArticleFacade = $categoryBlogArticleFacade;
        $this->productFacade = $productFacade;
        $this->gtmFacade = $gtmFacade;
        $this->listedProductViewFacade = $listedProductViewFacade;
        $this->brandFacade = $brandFacade;
        $this->discountExclusionFacade = $discountExclusionFacade;
        $this->listedProductViewElasticFacade = $listedProductViewElasticFacade;
        $this->pricingGroupFacade = $pricingGroupFacade;
        $this->flagFacade = $flagFacade;
        $this->heurekaReviewFacade = $heurekaReviewFacade;
    }

    /**
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function detailAction($id)
    {
        $product = $this->productOnCurrentDomainFacade->getVisibleProductById($id);
        $this->gtmFacade->onProductDetailPage($product);

        if ($product->isVariant()) {
            return $this->redirectToRoute('front_product_detail', ['id' => $product->getMainVariant()->getId()]);
        }

        $accessories = $this->listedProductViewFacade->getAllAccessories($product->getId());
        $domainId = $this->domain->getId();
        /** @var \App\Model\Customer\User\CustomerUser|null $customerUser */
        $customerUser = $this->getUser();

        return $this->render('Front/Content/Product/detail.html.twig', [
            'product' => $product,
            'accessories' => $accessories,
            'productVisibleProductCategoryDomains' => $this->categoryFacade->getProductVisibleAndListableProductCategoryDomains($product, $domainId),
            'domainId' => $domainId,
            'productBlogArticles' => $this->blogArticleFacade->getVisibleByProduct(
                $product,
                $domainId,
                $this->domain->getLocale(),
                self::PRODUCT_BLOG_ARTICLES_LIMIT
            ),
            'youtubeDetails' => $this->productFacade->getYoutubeViews($product),
            'registrationDiscountExclusionText' => $this->discountExclusionFacade->getRegistrationDiscountExclusionText($this->domain->getId()),
            'promoDiscountExclusionText' => $this->discountExclusionFacade->getPromoDiscountExclusionText($this->domain->getId()),
            'allDiscountExclusionText' => $this->discountExclusionFacade->getAllDiscountExclusionText($this->domain->getId()),
            'parentSetViews' => $this->listedProductViewElasticFacade->getParentSetsByProduct($product, $domainId, $this->pricingGroupFacade->getCurrentPricingGroup($customerUser)),
            'heurekaReviews' => $this->heurekaReviewFacade->getLatestReviews(),
        ]);
    }

    /**
     * @param int $productId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function boxTabsAction(int $productId)
    {
        $product = $this->productOnCurrentDomainFacade->getVisibleProductById($productId);
        $domainId = $this->domain->getId();
        $productMainCategory = $this->categoryFacade->getProductMainCategoryByDomainId($product, $domainId);

        return $this->render('Front/Content/Product/boxTabs.html.twig', [
            'product' => $product,
            'productMainCategory' => $productMainCategory,
            'youtubeDetails' => $this->productFacade->getYoutubeViews($product),
            'productVisibleProductCategoryDomains' => $this->categoryFacade->getProductVisibleAndListableProductCategoryDomains($product, $domainId),
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int $id
     */
    public function listByCategoryAction(Request $request, $id)
    {
        /** @var \App\Model\Category\Category $category */
        $category = $this->categoryFacade->getVisibleOnDomainById($this->domain->getId(), $id);

        $this->gtmFacade->onProductListByCategoryPage($category);

        $visibleChildren = $this->categoryFacade->getAllVisibleAndListableChildrenByCategoryAndDomainId($category, $this->domain->getId());

        if ($category->isPreListingCategory()) {
            return $this->render('Front/Content/Product/preListingCategoryList.html.twig', [
                'category' => $category,
                'visibleChildren' => $visibleChildren,
                'categoriesBlogArticles' => $this->categoryBlogArticleFacade->getVisibleBlogArticlesByCategoryAndDomainId(
                    $category,
                    $this->domain->getId(),
                    self::PRE_LIST_BLOG_ARTICLES_LIMIT
                ),
            ]);
        }

        $requestPage = $request->get(self::PAGE_QUERY_PARAMETER);
        if (!$this->isRequestPageValid($requestPage)) {
            return $this->redirectToRoute('front_product_list', $this->getRequestParametersWithoutPage());
        }
        $page = $requestPage === null ? 1 : (int)$requestPage;

        $orderingModeId = $this->productListOrderingModeForListFacade->getOrderingModeIdFromRequest($request);

        $productFilterData = new ProductFilterData();

        $productFilterConfig = $this->createProductFilterConfigForCategory($category);
        $filterForm = $this->createForm(ProductFilterFormType::class, $productFilterData, [
            'product_filter_config' => $productFilterConfig,
        ]);
        $filterForm->handleRequest($request);

        $paginationResult = $this->listedProductViewFacade->getFilteredPaginatedInCategory(
            $id,
            $productFilterData,
            $orderingModeId,
            $page,
            self::PRODUCTS_PER_PAGE,
            $category->isUnavailableProductsShown()
        );

        $productFilterCountData = null;
        if ($this->moduleFacade->isEnabled(ModuleList::PRODUCT_FILTER_COUNTS)) {
            $productFilterCountData = $this->productOnCurrentDomainFacade->getProductFilterCountDataInCategory(
                $id,
                $productFilterConfig,
                $productFilterData
            );
        }

        $viewParameters = [
            'paginationResult' => $paginationResult,
            'productFilterCountData' => $productFilterCountData,
            'category' => $category,
            'filterForm' => $filterForm->createView(),
            'filterFormSubmitted' => $filterForm->isSubmitted(),
            'visibleChildren' => $visibleChildren,
            'isSaleCategory' => false,
            'isNewsCategory' => false,
            'priceRange' => $productFilterConfig->getPriceRange(),
            'categoriesBlogArticles' => $this->categoryBlogArticleFacade->getVisibleBlogArticlesByCategoryAndDomainId(
                $category,
                $this->domain->getId(),
                self::LIST_BLOG_ARTICLES_LIMIT
            ),
            'allowBrandLinks' => !$this->isAnyFilterActive($productFilterData),
            'categoryTitle' => $this->getCategoryTitleWithActiveBrands($category, $productFilterData),
            'disableIndexing' => count($productFilterData->brands) >= 2,
            'heurekaReviews' => $this->heurekaReviewFacade->getLatestReviews(),
        ];

        if ($request->isXmlHttpRequest()) {
            return $this->render('Front/Content/Product/ajaxList.html.twig', $viewParameters);
        } else {
            return $this->render('Front/Content/Product/list.html.twig', $viewParameters);
        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listBySaleCategoryAction(Request $request, int $id): Response
    {
        $params = $request->query->get('product_filter_form');
        $tmpParams = $params;

        foreach ($this->flagFacade->getSaleFlags() as $saleFlag) {
            $tmpParams['flags'][] = $saleFlag->getId();
        }
        $request->query->set('product_filter_form', $tmpParams);

        $category = $this->categoryFacade->getById($id);

        $requestPage = $request->get(self::PAGE_QUERY_PARAMETER);
        if (!$this->isRequestPageValid($requestPage)) {
            return $this->redirectToRoute('front_sale_product_list', $this->getRequestParametersWithoutPage());
        }
        $page = $requestPage === null ? 1 : (int)$requestPage;

        $orderingModeId = $this->productListOrderingModeForListFacade->getOrderingModeIdFromRequest($request);

        $productFilterData = new ProductFilterData();
        $saleFlags = $this->flagFacade->getSaleFlags();

        foreach ($saleFlags as $saleFlag) {
            $productFilterData->flags[] = $saleFlag;
        }

        $productFilterConfig = $this->createProductFilterConfigForCategory($category, $saleFlags);
        $filterForm = $this->createForm(ProductFilterFormType::class, $productFilterData, [
            'product_filter_config' => $productFilterConfig,
        ]);

        $filterForm->handleRequest($request);

        $paginationResult = $this->listedProductViewFacade->getFilteredPaginatedInCategory(
            $category->getId(),
            $productFilterData,
            $orderingModeId,
            $page,
            self::PRODUCTS_PER_PAGE,
            $category->isUnavailableProductsShown()
        );

        $productFilterCountData = null;
        if ($this->moduleFacade->isEnabled(ModuleList::PRODUCT_FILTER_COUNTS)) {
            $productFilterCountData = $this->productOnCurrentDomainFacade->getProductFilterCountDataInCategory(
                $category->getId(),
                $productFilterConfig,
                $productFilterData
            );
        }

        $viewParameters = [
            'paginationResult' => $paginationResult,
            'productFilterCountData' => $productFilterCountData,
            'category' => $category,
            'filterForm' => $filterForm->createView(),
            'filterFormSubmitted' => $filterForm->isSubmitted(),
            'visibleChildren' => null,
            'isSaleCategory' => true,
            'isNewsCategory' => false,
            'priceRange' => $productFilterConfig->getPriceRange(),
            'categoriesBlogArticles' => $this->categoryBlogArticleFacade->getVisibleBlogArticlesByCategoryAndDomainId(
                $category,
                $this->domain->getId(),
                self::LIST_BLOG_ARTICLES_LIMIT
            ),
            'allowBrandLinks' => !$this->isAnyFilterActive($productFilterData, true),
            'categoryTitle' => $this->getCategoryTitleWithActiveBrands($category, $productFilterData, Category::SALE_TYPE),
            'disableIndexing' => count($productFilterData->brands) >= 2,
            'heurekaReviews' => $this->heurekaReviewFacade->getLatestReviews(),
        ];

        $request->query->set('product_filter_form', $params);

        if ($request->isXmlHttpRequest()) {
            return $this->render('Front/Content/Product/ajaxList.html.twig', $viewParameters);
        } else {
            return $this->render('Front/Content/Product/list.html.twig', $viewParameters);
        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listByNewsCategoryAction(Request $request, int $id): Response
    {
        $params = $request->query->get('product_filter_form');
        $tmpParams = $params;

        foreach ($this->flagFacade->getSaleFlags() as $newsFlag) {
            $tmpParams['flags'][] = $newsFlag->getId();
        }
        $request->query->set('product_filter_form', $tmpParams);

        $category = $this->categoryFacade->getById($id);

        $requestPage = $request->get(self::PAGE_QUERY_PARAMETER);
        if (!$this->isRequestPageValid($requestPage)) {
            return $this->redirectToRoute('front_sale_product_list', $this->getRequestParametersWithoutPage());
        }
        $page = $requestPage === null ? 1 : (int)$requestPage;

        $orderingModeId = $this->productListOrderingModeForListFacade->getOrderingModeIdFromRequest($request);

        $productFilterData = new ProductFilterData();
        $newsFlags = $this->flagFacade->getNewsFlags();

        foreach ($newsFlags as $newsFlag) {
            $productFilterData->flags[] = $newsFlag;
        }

        $productFilterConfig = $this->createProductFilterConfigForCategory($category, $newsFlags);
        $filterForm = $this->createForm(ProductFilterFormType::class, $productFilterData, [
            'product_filter_config' => $productFilterConfig,
        ]);

        $filterForm->handleRequest($request);

        $paginationResult = $this->listedProductViewFacade->getFilteredPaginatedInCategory(
            $category->getId(),
            $productFilterData,
            $orderingModeId,
            $page,
            self::PRODUCTS_PER_PAGE,
            $category->isUnavailableProductsShown()
        );

        $productFilterCountData = null;
        if ($this->moduleFacade->isEnabled(ModuleList::PRODUCT_FILTER_COUNTS)) {
            $productFilterCountData = $this->productOnCurrentDomainFacade->getProductFilterCountDataInCategory(
                $category->getId(),
                $productFilterConfig,
                $productFilterData
            );
        }

        $viewParameters = [
            'paginationResult' => $paginationResult,
            'productFilterCountData' => $productFilterCountData,
            'category' => $category,
            'filterForm' => $filterForm->createView(),
            'filterFormSubmitted' => $filterForm->isSubmitted(),
            'visibleChildren' => null,
            'isSaleCategory' => false,
            'isNewsCategory' => true,
            'priceRange' => $productFilterConfig->getPriceRange(),
            'categoriesBlogArticles' => $this->categoryBlogArticleFacade->getVisibleBlogArticlesByCategoryAndDomainId(
                $category,
                $this->domain->getId(),
                self::LIST_BLOG_ARTICLES_LIMIT
            ),
            'allowBrandLinks' => !$this->isAnyFilterActive($productFilterData, true),
            'categoryTitle' => $this->getCategoryTitleWithActiveBrands($category, $productFilterData, Category::NEWS_TYPE),
            'disableIndexing' => count($productFilterData->brands) >= 2,
            'heurekaReviews' => $this->heurekaReviewFacade->getLatestReviews(),
        ];

        $request->query->set('product_filter_form', $params);

        if ($request->isXmlHttpRequest()) {
            return $this->render('Front/Content/Product/ajaxList.html.twig', $viewParameters);
        } else {
            return $this->render('Front/Content/Product/list.html.twig', $viewParameters);
        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function searchAction(Request $request)
    {
        $searchText = $request->query->get(self::SEARCH_TEXT_PARAMETER, '');

        $requestPageProducts = $request->get(self::PAGE_QUERY_PARAMETER);
        if (!$this->isRequestPageValid($requestPageProducts)) {
            return $this->redirectToRoute('front_product_search', $this->getRequestParametersWithoutPage());
        }
        $pageProducts = $requestPageProducts === null ? 1 : (int)$requestPageProducts;

        $orderingModeId = $this->productListOrderingModeForSearchFacade->getOrderingModeIdFromRequest(
            $request
        );

        $productFilterData = new ProductFilterData();

        $productFilterConfig = $this->createProductFilterConfigForSearch($searchText);
        $filterForm = $this->createForm(ProductFilterFormType::class, $productFilterData, [
            'product_filter_config' => $productFilterConfig,
        ]);
        $filterForm->handleRequest($request);

        $paginationResultProducts = $this->listedProductViewElasticFacade->getFilteredPaginatedForSearch(
            $searchText,
            $productFilterData,
            $orderingModeId,
            $pageProducts,
            self::PRODUCTS_PER_PAGE
        );

        $productFilterCountData = null;
        if ($this->moduleFacade->isEnabled(ModuleList::PRODUCT_FILTER_COUNTS)) {
            $productFilterCountData = $this->productOnCurrentDomainFacade->getProductFilterCountDataForSearch(
                $searchText,
                $productFilterConfig,
                $productFilterData
            );
        }

        $viewParameters = [
            'paginationResultProducts' => $paginationResultProducts,
            'productFilterCountData' => $productFilterCountData,
            'filterForm' => $filterForm->createView(),
            'filterFormSubmitted' => $filterForm->isSubmitted(),
            'searchText' => $searchText,
            'SEARCH_TEXT_PARAMETER' => self::SEARCH_TEXT_PARAMETER,
            'priceRange' => $productFilterConfig->getPriceRange(),
            'allowBrandLinks' => !$this->isAnyFilterActive($productFilterData),
            'disableIndexing' => count($productFilterData->brands) >= 2,
            'heurekaReviews' => $this->heurekaReviewFacade->getLatestReviews(),
        ];

        if ($request->isXmlHttpRequest()) {
            return $this->render('Front/Content/Product/ajaxSearch.html.twig', $viewParameters);
        } else {
            $viewParameters['foundCategories'] = $this->searchCategories($searchText);
            $viewParameters['paginationResultSets'] = $this->getPaginationResultSets($searchText, $orderingModeId, 1);
            return $this->render('Front/Content/Product/search.html.twig', $viewParameters);
        }
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Paginator\PaginationResult $paginationResultSets
     * @param string $searchText
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderSetsBySearchTextAction(PaginationResult $paginationResultSets, string $searchText): Response
    {
        return $this->render('Front/Content/Product/setList.html.twig', [
            'paginationResultSets' => $paginationResultSets,
            'searchText' => $searchText,
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchSetsAction(Request $request): Response
    {
        $searchText = $request->query->get(self::SEARCH_TEXT_PARAMETER, '');
        $orderingModeId = $this->productListOrderingModeForSearchFacade->getOrderingModeIdFromRequest(
            $request
        );
        $requestPage = $request->get(self::PAGE_SETS_QUERY_PARAMETER);
        $page = $requestPage === null ? 1 : (int)$requestPage;

        $paginationResultSets = $this->getPaginationResultSets($searchText, $orderingModeId, $page);

        return $this->renderSetsBySearchTextAction($paginationResultSets, $searchText);
    }

    /**
     * @param \App\Model\Category\Category $category
     * @param \App\Model\Product\Flag\Flag[] $onlyFlags
     * @return \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfig
     */
    private function createProductFilterConfigForCategory(BaseCategory $category, array $onlyFlags = [])
    {
        return $this->productFilterConfigFactory->createForCategory(
            $this->domain->getId(),
            $this->domain->getLocale(),
            $category,
            $onlyFlags
        );
    }

    /**
     * @param string|null $searchText
     * @return \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfig
     */
    private function createProductFilterConfigForSearch($searchText)
    {
        return $this->productFilterConfigFactory->createForSearch(
            $this->domain->getId(),
            $this->domain->getLocale(),
            $searchText
        );
    }

    /**
     * @param string|null $searchText
     * @return \App\Model\Category\Category[]
     */
    private function searchCategories($searchText)
    {
        return $this->categoryFacade->getVisibleAndListableByDomainAndSearchText(
            $this->domain->getId(),
            $this->domain->getLocale(),
            $searchText
        );
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function selectOrderingModeForListAction(Request $request)
    {
        $productListOrderingConfig = $this->productListOrderingModeForListFacade->getProductListOrderingConfig();

        $orderingModeId = $this->productListOrderingModeForListFacade->getOrderingModeIdFromRequest(
            $request
        );

        return $this->render('Front/Content/Product/orderingSetting.html.twig', [
            'orderingModesNames' => $productListOrderingConfig->getSupportedOrderingModesNamesIndexedById(),
            'activeOrderingModeId' => $orderingModeId,
            'cookieName' => $productListOrderingConfig->getCookieName(),
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function selectOrderingModeForListByBrandAction(Request $request)
    {
        $productListOrderingConfig = $this->productListOrderingModeForBrandFacade->getProductListOrderingConfig();

        $orderingModeId = $this->productListOrderingModeForBrandFacade->getOrderingModeIdFromRequest(
            $request
        );

        return $this->render('Front/Content/Product/orderingSetting.html.twig', [
            'orderingModesNames' => $productListOrderingConfig->getSupportedOrderingModesNamesIndexedById(),
            'activeOrderingModeId' => $orderingModeId,
            'cookieName' => $productListOrderingConfig->getCookieName(),
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function selectOrderingModeForSearchAction(Request $request)
    {
        $productListOrderingConfig = $this->productListOrderingModeForSearchFacade->getProductListOrderingConfig();

        $orderingModeId = $this->productListOrderingModeForSearchFacade->getOrderingModeIdFromRequest(
            $request
        );

        return $this->render('Front/Content/Product/orderingSetting.html.twig', [
            'orderingModesNames' => $productListOrderingConfig->getSupportedOrderingModesNamesIndexedById(),
            'activeOrderingModeId' => $orderingModeId,
            'cookieName' => $productListOrderingConfig->getCookieName(),
        ]);
    }

    /**
     * @param string|null $page
     * @return bool
     */
    private function isRequestPageValid($page)
    {
        return $page === null || (preg_match('@^([2-9]|[1-9][0-9]+)$@', $page));
    }

    /**
     * @return array
     */
    private function getRequestParametersWithoutPage()
    {
        $parameters = $this->requestExtension->getAllRequestParams();
        unset($parameters[self::PAGE_QUERY_PARAMETER]);
        return $parameters;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int $id
     */
    public function listByBrandAction(Request $request, $id)
    {
        $requestPage = $request->get(self::PAGE_QUERY_PARAMETER);
        if (!$this->isRequestPageValid($requestPage)) {
            return $this->redirectToRoute('front_brand_detail', $this->getRequestParametersWithoutPage());
        }
        $page = $requestPage === null ? 1 : (int)$requestPage;

        $orderingModeId = $this->productListOrderingModeForBrandFacade->getOrderingModeIdFromRequest(
            $request
        );

        $paginationResult = $this->listedProductViewFacade->getPaginatedForBrand(
            $id,
            $orderingModeId,
            $page,
            self::PRODUCTS_PER_PAGE
        );

        $brand = $this->brandFacade->getById($id);

        $viewParameters = [
            'paginationResult' => $paginationResult,
            'brand' => $brand,
        ];

        if ($request->isXmlHttpRequest()) {
            return $this->render('Front/Content/Product/ajaxListByBrand.html.twig', $viewParameters);
        } else {
            return $this->render('Front/Content/Product/listByBrand.html.twig', $viewParameters);
        }
    }

    /**
     * @param string $searchText
     * @param string $orderingModeId
     * @param int $page
     * @return \Shopsys\FrameworkBundle\Component\Paginator\PaginationResult
     */
    private function getPaginationResultSets(string $searchText, string $orderingModeId, int $page): PaginationResult
    {
        return $this->listedProductViewElasticFacade->getFilteredPaginatedForSearch(
            $searchText,
            new ProductFilterData(),
            $orderingModeId,
            $page,
            self::PRODUCTS_PER_PAGE,
            Product::POHODA_PRODUCT_TYPE_ID_PRODUCT_SET
        );
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterData $productFilterData
     * @param bool $isSpecialCategory
     * @return bool
     */
    private function isAnyFilterActive(ProductFilterData $productFilterData, bool $isSpecialCategory = false): bool
    {
        if ($productFilterData->minimalPrice !== null) {
            return true;
        }

        if ($productFilterData->maximalPrice !== null) {
            return true;
        }

        if ($productFilterData->inStock === true) {
            return true;
        }

        if ($isSpecialCategory) {
            if (count($productFilterData->flags) > 1) {
                return true;
            }
        } else {
            if (!empty($productFilterData->flags)) {
                return true;
            }
        }

        if (!empty($productFilterData->brands)) {
            return true;
        }

        foreach ($productFilterData->parameters as $parameter) {
            if (!empty($parameter->values)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \App\Model\Category\Category $category
     * @param \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterData $productFilterData
     * @param string|null $type
     * @return string
     */
    private function getCategoryTitleWithActiveBrands(Category $category, ProductFilterData $productFilterData, ?string $type = null): string
    {
        $categoryTitle = '';

        if ($type === Category::SALE_TYPE) {
            $categoryTitle .= t('Výprodej') . ' - ';
        } elseif ($type === Category::NEWS_TYPE) {
            $categoryTitle .= t('Novinky') . ' - ';
        }

        $categoryTitle .= $category->getTitle($this->domain);

        if (!empty($productFilterData->brands)) {
            $categoryTitle .= ' - ' . implode(', ', array_map(fn (Brand $brand) => $brand->getName(), $productFilterData->brands));
        }

        return $categoryTitle;
    }
}
