<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Front;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Category\Category;
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
use Shopsys\ShopBundle\Component\Setting\Setting;
use Shopsys\ShopBundle\Form\Front\Product\ProductFilterFormType;
use Shopsys\ShopBundle\Model\Blog\Article\BlogArticleFacade;
use Shopsys\ShopBundle\Model\Category\CategoryBlogArticle\CategoryBlogArticleFacade;
use Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroupFacade;
use Shopsys\ShopBundle\Model\Product\ProductFacade;
use Symfony\Component\HttpFoundation\Request;

class ProductController extends FrontBaseController
{
    public const SEARCH_TEXT_PARAMETER = 'q';
    public const PAGE_QUERY_PARAMETER = 'page';
    public const PRODUCTS_PER_PAGE = 12;
    private const PRODUCT_BLOG_ARTICLES_LIMIT = 2;
    private const LIST_BLOG_ARTICLES_LIMIT = 1;
    private const PRE_LIST_BLOG_ARTICLES_LIMIT = 2;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfigFactory
     */
    private $productFilterConfigFactory;

    /**
     * @var \Shopsys\ShopBundle\Model\Category\CategoryFacade
     */
    private $categoryFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductOnCurrentDomainElasticFacade
     */
    private $productOnCurrentDomainFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Twig\RequestExtension
     */
    private $requestExtension;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Listing\ProductListOrderingModeForListFacade
     */
    private $productListOrderingModeForListFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Listing\ProductListOrderingModeForBrandFacade
     */
    private $productListOrderingModeForBrandFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Listing\ProductListOrderingModeForSearchFacade
     */
    private $productListOrderingModeForSearchFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Module\ModuleFacade
     */
    private $moduleFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Brand\BrandFacade
     */
    private $brandFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroupFacade
     */
    private $mainVariantGroupFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleFacade
     */
    private $blogArticleFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Category\CategoryBlogArticle\CategoryBlogArticleFacade
     */
    private $categoryBlogArticleFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \Shopsys\ShopBundle\Component\Setting\Setting
     */
    private $setting;

    /**
     * @param \Shopsys\FrameworkBundle\Twig\RequestExtension $requestExtension
     * @param \Shopsys\FrameworkBundle\Model\Category\CategoryFacade $categoryFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductOnCurrentDomainFacadeInterface $productOnCurrentDomainFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfigFactory $productFilterConfigFactory
     * @param \Shopsys\FrameworkBundle\Model\Product\Listing\ProductListOrderingModeForListFacade $productListOrderingModeForListFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Listing\ProductListOrderingModeForBrandFacade $productListOrderingModeForBrandFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Listing\ProductListOrderingModeForSearchFacade $productListOrderingModeForSearchFacade
     * @param \Shopsys\FrameworkBundle\Model\Module\ModuleFacade $moduleFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Brand\BrandFacade $brandFacade
     * @param \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroupFacade $mainVariantGroupFacade
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleFacade $blogArticleFacade
     * @param \Shopsys\ShopBundle\Model\Category\CategoryBlogArticle\CategoryBlogArticleFacade $categoryBlogArticleFacade
     * @param \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade
     * @param \Shopsys\ShopBundle\Component\Setting\Setting $setting
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
        BrandFacade $brandFacade,
        MainVariantGroupFacade $mainVariantGroupFacade,
        BlogArticleFacade $blogArticleFacade,
        CategoryBlogArticleFacade $categoryBlogArticleFacade,
        ProductFacade $productFacade,
        Setting $setting
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
        $this->brandFacade = $brandFacade;
        $this->mainVariantGroupFacade = $mainVariantGroupFacade;
        $this->blogArticleFacade = $blogArticleFacade;
        $this->categoryBlogArticleFacade = $categoryBlogArticleFacade;
        $this->productFacade = $productFacade;
        $this->setting = $setting;
    }

    /**
     * @param int $id
     */
    public function detailAction($id)
    {
        /** @var \Shopsys\ShopBundle\Model\Product\Product $product */
        $product = $this->productOnCurrentDomainFacade->getVisibleProductById($id);

        if ($product->isVariant()) {
            return $this->redirectToRoute('front_product_detail', ['id' => $product->getMainVariant()->getId()]);
        }

        $accessories = $this->productOnCurrentDomainFacade->getAccessoriesForProduct($product);
        $domainId = $this->domain->getId();
        $productMainCategory = $this->categoryFacade->getProductMainCategoryByDomainId($product, $domainId);
        $mainVariantGroupProducts = $this->mainVariantGroupFacade->getProductsForMainVariantGroup($product);
        $youtubeDetailForMainVariants = $this->productFacade->getYoutubeViewForMainVariants($mainVariantGroupProducts);

        if (count($mainVariantGroupProducts) > 0) {
            $allVariants = $this->productOnCurrentDomainFacade->getVariantsForProducts($mainVariantGroupProducts);
        } else {
            $allVariants = $this->productOnCurrentDomainFacade->getVariantsForProduct($product);
        }

        return $this->render('@ShopsysShop/Front/Content/Product/detail.html.twig', [
            'product' => $product,
            'accessories' => $accessories,
            'allVariants' => $allVariants,
            'productMainCategory' => $productMainCategory,
            'productVisibleProductCategoryDomains' => $this->categoryFacade->getProductVisibleProductCategoryDomains($product, $domainId),
            'mainVariants' => $mainVariantGroupProducts,
            'youtubeDetailForMainVariants' => $youtubeDetailForMainVariants,
            'domainId' => $domainId,
            'productBlogArticles' => $this->blogArticleFacade->getVisibleByProduct(
                $product,
                $domainId,
                $this->domain->getLocale(),
                self::PRODUCT_BLOG_ARTICLES_LIMIT
            ),
            'youtubeDetail' => $this->productFacade->getYoutubeView($product),
            'productSizeArticleId' => $this->setting->getForDomain(Setting::PRODUCT_SIZE_ARTICLE_ID, $domainId),
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int $id
     */
    public function listByCategoryAction(Request $request, $id)
    {
        /** @var \Shopsys\ShopBundle\Model\Category\Category $category */
        $category = $this->categoryFacade->getVisibleOnDomainById($this->domain->getId(), $id);
        $visibleChildren = $this->categoryFacade->getAllVisibleChildrenByCategoryAndDomainId($category, $this->domain->getId());

        if ($category->isPreListingCategory()) {
            return $this->render('@ShopsysShop/Front/Content/Product/preListingCategoryList.html.twig', [
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

        $paginationResult = $this->productOnCurrentDomainFacade->getPaginatedProductsInCategory(
            $productFilterData,
            $orderingModeId,
            $page,
            self::PRODUCTS_PER_PAGE,
            $id
        );

        $productFilterCountData = null;
        if ($this->moduleFacade->isEnabled(ModuleList::PRODUCT_FILTER_COUNTS)) {
            $productFilterCountData = $this->productOnCurrentDomainFacade->getProductFilterCountDataInCategory(
                $id,
                $productFilterConfig,
                $productFilterData
            );
        }

        $variantsIndexedByMainVariantId = $this->productOnCurrentDomainFacade->getVariantsIndexedByMainVariantId($paginationResult->getResults());
        $mainVariantsIndexedByMainVariantGroup = $this->mainVariantGroupFacade->getProductsIndexedByMainVariantGroup($paginationResult->getResults());

        $viewParameters = [
            'paginationResult' => $paginationResult,
            'productFilterCountData' => $productFilterCountData,
            'category' => $category,
            'filterForm' => $filterForm->createView(),
            'filterFormSubmitted' => $filterForm->isSubmitted(),
            'visibleChildren' => $visibleChildren,
            'priceRange' => $productFilterConfig->getPriceRange(),
            'variantsIndexedByMainVariantId' => $variantsIndexedByMainVariantId,
            'mainVariantsIndexedByMainVariantGroup' => $mainVariantsIndexedByMainVariantGroup,
            'categoriesBlogArticles' => $this->categoryBlogArticleFacade->getVisibleBlogArticlesByCategoryAndDomainId(
                $category,
                $this->domain->getId(),
                self::LIST_BLOG_ARTICLES_LIMIT
            ),
        ];

        if ($request->isXmlHttpRequest()) {
            return $this->render('@ShopsysShop/Front/Content/Product/ajaxList.html.twig', $viewParameters);
        } else {
            return $this->render('@ShopsysShop/Front/Content/Product/list.html.twig', $viewParameters);
        }
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

        $paginationResult = $this->productOnCurrentDomainFacade->getPaginatedProductsForBrand(
            $orderingModeId,
            $page,
            self::PRODUCTS_PER_PAGE,
            $id
        );

        $brand = $this->brandFacade->getById($id);

        $variantsIndexedByMainVariantId = $this->productOnCurrentDomainFacade->getVariantsIndexedByMainVariantId($paginationResult->getResults());
        $mainVariantsIndexedByMainVariantGroup = $this->mainVariantGroupFacade->getProductsIndexedByMainVariantGroup($paginationResult->getResults());

        $viewParameters = [
            'paginationResult' => $paginationResult,
            'brand' => $brand,
            'variantsIndexedByMainVariantId' => $variantsIndexedByMainVariantId,
            'mainVariantsIndexedByMainVariantGroup' => $mainVariantsIndexedByMainVariantGroup,
        ];

        if ($request->isXmlHttpRequest()) {
            return $this->render('@ShopsysShop/Front/Content/Product/ajaxListByBrand.html.twig', $viewParameters);
        } else {
            return $this->render('@ShopsysShop/Front/Content/Product/listByBrand.html.twig', $viewParameters);
        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function searchAction(Request $request)
    {
        $searchText = $request->query->get(self::SEARCH_TEXT_PARAMETER);

        $requestPage = $request->get(self::PAGE_QUERY_PARAMETER);
        if (!$this->isRequestPageValid($requestPage)) {
            return $this->redirectToRoute('front_product_search', $this->getRequestParametersWithoutPage());
        }
        $page = $requestPage === null ? 1 : (int)$requestPage;

        $orderingModeId = $this->productListOrderingModeForSearchFacade->getOrderingModeIdFromRequest(
            $request
        );

        $productFilterData = new ProductFilterData();

        $productFilterConfig = $this->createProductFilterConfigForSearch($searchText);
        $filterForm = $this->createForm(ProductFilterFormType::class, $productFilterData, [
            'product_filter_config' => $productFilterConfig,
        ]);
        $filterForm->handleRequest($request);

        $paginationResult = $this->productOnCurrentDomainFacade->getPaginatedProductsForSearch(
            $searchText,
            $productFilterData,
            $orderingModeId,
            $page,
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

        $variantsIndexedByMainVariantId = $this->productOnCurrentDomainFacade->getVariantsIndexedByMainVariantId($paginationResult->getResults());
        $mainVariantsIndexedByMainVariantGroup = $this->mainVariantGroupFacade->getProductsIndexedByMainVariantGroup($paginationResult->getResults());

        $viewParameters = [
            'paginationResult' => $paginationResult,
            'productFilterCountData' => $productFilterCountData,
            'filterForm' => $filterForm->createView(),
            'filterFormSubmitted' => $filterForm->isSubmitted(),
            'searchText' => $searchText,
            'SEARCH_TEXT_PARAMETER' => self::SEARCH_TEXT_PARAMETER,
            'priceRange' => $productFilterConfig->getPriceRange(),
            'variantsIndexedByMainVariantId' => $variantsIndexedByMainVariantId,
            'mainVariantsIndexedByMainVariantGroup' => $mainVariantsIndexedByMainVariantGroup,
        ];

        if ($request->isXmlHttpRequest()) {
            return $this->render('@ShopsysShop/Front/Content/Product/ajaxSearch.html.twig', $viewParameters);
        } else {
            $viewParameters['foundCategories'] = $this->searchCategories($searchText);
            return $this->render('@ShopsysShop/Front/Content/Product/search.html.twig', $viewParameters);
        }
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Category\Category $category
     * @return \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfig
     */
    private function createProductFilterConfigForCategory(Category $category)
    {
        return $this->productFilterConfigFactory->createForCategory(
            $this->domain->getId(),
            $this->domain->getLocale(),
            $category
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
     * @return \Shopsys\FrameworkBundle\Model\Category\Category[]
     */
    private function searchCategories($searchText)
    {
        return $this->categoryFacade->getVisibleByDomainAndSearchText(
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

        return $this->render('@ShopsysShop/Front/Content/Product/orderingSetting.html.twig', [
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

        return $this->render('@ShopsysShop/Front/Content/Product/orderingSetting.html.twig', [
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

        return $this->render('@ShopsysShop/Front/Content/Product/orderingSetting.html.twig', [
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
}
