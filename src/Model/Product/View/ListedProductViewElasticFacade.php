<?php

declare(strict_types=1);

namespace App\Model\Product\View;

use App\Model\Pricing\Group\PricingGroup;
use App\Model\Pricing\Group\PricingGroupFacade;
use App\Model\Product\BestsellingProduct\CachedBestsellingProductFacade;
use App\Model\Product\Flag\Flag;
use App\Model\Product\Flag\FlagFacade;
use App\Model\Product\LastVisitedProducts\LastVisitedProductsFacade;
use App\Model\Product\Listing\ProductListOrderingConfig;
use App\Model\Product\PriceBombProduct\PriceBombProductFacade;
use App\Model\Product\Product;
use App\Model\Product\Set\ProductSet;
use App\Model\Product\Set\ProductSetFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Paginator\PaginationResult;
use Shopsys\FrameworkBundle\Model\Category\Category;
use Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser;
use Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessoryFacade;
use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterData;
use Shopsys\FrameworkBundle\Model\Product\Product as BaseProduct;
use Shopsys\FrameworkBundle\Model\Product\ProductFacade;
use Shopsys\FrameworkBundle\Model\Product\ProductOnCurrentDomainFacadeInterface;
use Shopsys\FrameworkBundle\Model\Product\TopProduct\TopProductFacade;
use Shopsys\ReadModelBundle\Image\ImageViewFacade;
use Shopsys\ReadModelBundle\Product\Action\ProductActionViewFacade;
use Shopsys\ReadModelBundle\Product\Listed\ListedProductViewElasticFacade as BaseListedProductViewElasticFacade;
use Shopsys\ReadModelBundle\Product\Listed\ListedProductViewFactory;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @property \App\Model\Product\View\ListedProductViewFactory $listedProductViewFactory
 * @property \App\Model\Product\ProductOnCurrentDomainElasticFacade $productOnCurrentDomainFacade
 * @property \App\Model\Product\ProductFacade $productFacade
 * @method \App\Model\Product\View\ListedProductView[] createFromProducts(array $products)
 */
class ListedProductViewElasticFacade extends BaseListedProductViewElasticFacade
{
    /**
     * @var \App\Model\Pricing\Group\PricingGroupFacade
     */
    private $pricingGroupFacade;

    /**
     * @var \App\Model\Product\BestsellingProduct\CachedBestsellingProductFacade
     */
    private $cachedBestsellingProductFacade;

    /**
     * @var \App\Model\Product\LastVisitedProducts\LastVisitedProductsFacade
     */
    private $lastVisitedProductsFacade;

    /**
     * @var \App\Model\Product\PriceBombProduct\PriceBombProductFacade
     */
    protected $priceBombProductFacade;

    /**
     * @var \App\Model\Product\Set\ProductSetFacade
     */
    private $productSetFacade;

    /**
     * @var \App\Model\Product\Flag\FlagFacade
     */
    private $flagFacade;

    /**
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessoryFacade $productAccessoryFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser $currentCustomerUser
     * @param \Shopsys\FrameworkBundle\Model\Product\TopProduct\TopProductFacade $topProductFacade
     * @param \App\Model\Product\ProductOnCurrentDomainElasticFacade $productOnCurrentDomainFacade
     * @param \Shopsys\ReadModelBundle\Product\Listed\ListedProductViewFactory $listedProductViewFactory
     * @param \Shopsys\ReadModelBundle\Product\Action\ProductActionViewFacade $productActionViewFacade
     * @param \Shopsys\ReadModelBundle\Image\ImageViewFacade $imageViewFacade
     * @param \App\Model\Product\BestsellingProduct\CachedBestsellingProductFacade $cachedBestsellingProductFacade
     * @param \App\Model\Product\LastVisitedProducts\LastVisitedProductsFacade $lastVisitedProductsFacade
     * @param \App\Model\Product\PriceBombProduct\PriceBombProductFacade $priceBombProductFacade
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \App\Model\Product\Set\ProductSetFacade $productSetFacade
     * @param \App\Model\Product\Flag\FlagFacade $flagFacade
     */
    public function __construct(
        ProductFacade $productFacade,
        ProductAccessoryFacade $productAccessoryFacade,
        Domain $domain,
        CurrentCustomerUser $currentCustomerUser,
        TopProductFacade $topProductFacade,
        ProductOnCurrentDomainFacadeInterface $productOnCurrentDomainFacade,
        ListedProductViewFactory $listedProductViewFactory,
        ProductActionViewFacade $productActionViewFacade,
        ImageViewFacade $imageViewFacade,
        CachedBestsellingProductFacade $cachedBestsellingProductFacade,
        LastVisitedProductsFacade $lastVisitedProductsFacade,
        PriceBombProductFacade $priceBombProductFacade,
        PricingGroupFacade $pricingGroupFacade,
        ProductSetFacade $productSetFacade,
        FlagFacade $flagFacade
    ) {
        parent::__construct($productFacade, $productAccessoryFacade, $domain, $currentCustomerUser, $topProductFacade, $productOnCurrentDomainFacade, $listedProductViewFactory, $productActionViewFacade, $imageViewFacade);
        $this->cachedBestsellingProductFacade = $cachedBestsellingProductFacade;
        $this->lastVisitedProductsFacade = $lastVisitedProductsFacade;
        $this->priceBombProductFacade = $priceBombProductFacade;
        $this->pricingGroupFacade = $pricingGroupFacade;
        $this->productSetFacade = $productSetFacade;
        $this->flagFacade = $flagFacade;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\ParameterBag $cookies
     * @param int $limit
     * @return \Shopsys\ReadModelBundle\Product\Listed\ListedProductView[]
     */
    public function getProductsFromCookieSortedByNewest(ParameterBag $cookies, int $limit): array
    {
        $lastVisitedProductIds = $this->lastVisitedProductsFacade->getProductIdsFromCookieSortedByNewest(
            $cookies,
            $limit
        );

        return $this->createFromArray($this->productOnCurrentDomainFacade->getSellableHitsForIds($lastVisitedProductIds));
    }

    /**
     * @param \App\Model\Category\Category $category
     * @return \Shopsys\ReadModelBundle\Product\Listed\ListedProductView[]
     */
    public function getAllOfferedBestsellingProducts(Category $category): array
    {
        $bestsellingProductIds = $this->cachedBestsellingProductFacade->getAllOfferedBestsellingProductIds(
            $this->domain->getId(),
            $category,
            $this->currentCustomerUser->getPricingGroup()
        );

        return $this->createFromArray($this->productOnCurrentDomainFacade->getSellableHitsForIds($bestsellingProductIds));
    }

    /**
     * @param int|null $limit
     * @return \Shopsys\ReadModelBundle\Product\Listed\ListedProductView[]
     */
    public function getPriceBombProducts(?int $limit = null): array
    {
        $priceBombProducts = $this->priceBombProductFacade->getPriceBombProducts(
            $this->domain->getId(),
            $this->currentCustomerUser->getPricingGroup(),
            $limit
        );

        return $this->createFromProducts($priceBombProducts);
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \App\Model\Product\View\ListedProductView[]
     */
    public function getParentSetsByProduct(Product $product, int $domainId, PricingGroup $pricingGroup): array
    {
        return $this->createFromProducts(array_map(function (ProductSet $productSet) {
            return $productSet->getMainProduct();
        }, $this->productSetFacade->getVisibleByItem($product, $domainId, $pricingGroup)));
    }

    /**
     * @param int[] $flagIds
     * @param string $orderingModeId
     * @param int $page
     * @param int $limit
     * @return \Shopsys\FrameworkBundle\Component\Paginator\PaginationResult
     */
    private function getPaginatedForFlags(array $flagIds, string $orderingModeId, int $page, int $limit): PaginationResult
    {
        $paginationResult = $this->productOnCurrentDomainFacade->getPaginatedProductsForFlags($orderingModeId, $page, $limit, $flagIds);

        return $this->createPaginationResultWithArray($paginationResult);
    }

    /**
     * @param int $limit
     * @return \App\Model\Product\View\ListedProductView[]
     */
    public function getProductsWithNewsFlags(int $limit): array
    {
        $newsFlags = $this->flagFacade->getNewsFlags();
        $newsFlagsIds = array_map(function (Flag $flag) {
            return $flag->getId();
        }, $newsFlags);

        return $this->getPaginatedForFlags($newsFlagsIds, ProductListOrderingConfig::ORDER_BY_PRIORITY, 1, $limit)->getResults();
    }

    /**
     * @param array $productsArray
     * @return \App\Model\Product\View\ListedProductView[]
     */
    protected function createFromArray(array $productsArray): array
    {
        $productIds = [];

        foreach ($productsArray as $productArray) {
            $productIds[] = $productArray['id'];

            foreach ($productArray['set_items'] as $setItem) {
                $productIds[] = $setItem['id'];
            }
        }

        $imageViews = $this->imageViewFacade->getForEntityIds(BaseProduct::class, $productIds);
        $salePricingGroup = $this->pricingGroupFacade->getSalePricePricingGroup($this->domain->getId());

        $listedProductViews = [];
        foreach ($productsArray as $productArray) {
            $productId = $productArray['id'];

            foreach ($productArray['set_items'] as &$setItem) {
                $setItem['image'] = $imageViews[$setItem['id']];
            }

            $listedProductViews[$productId] = $this->listedProductViewFactory->createFromArray(
                $productArray,
                $imageViews[$productId],
                $this->productActionViewFacade->getForArray($productArray),
                $productArray['is_in_any_sale_stock'] === true ? $salePricingGroup : $this->currentCustomerUser->getPricingGroup()
            );
        }

        return $listedProductViews;
    }

    /**
     * @param string $searchText
     * @param \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterData $filterData
     * @param string $orderingModeId
     * @param int $page
     * @param int $limit
     * @param int $pohodaProductType
     * @return \Shopsys\FrameworkBundle\Component\Paginator\PaginationResult
     */
    public function getFilteredPaginatedForSearch(
        string $searchText,
        ProductFilterData $filterData,
        string $orderingModeId,
        int $page,
        int $limit,
        int $pohodaProductType = Product::POHODA_PRODUCT_TYPE_ID_SINGLE_PRODUCT
    ): PaginationResult {
        $paginationResult = $this->productOnCurrentDomainFacade->getPaginatedProductsForSearch($searchText, $filterData, $orderingModeId, $page, $limit, $pohodaProductType);

        return $this->createPaginationResultWithArray($paginationResult);
    }

    /**
     * @param int $categoryId
     * @param \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterData $filterData
     * @param string $orderingModeId
     * @param int $page
     * @param int $limit
     * @param bool $showUnavailableProducts
     * @return \Shopsys\FrameworkBundle\Component\Paginator\PaginationResult
     */
    public function getFilteredPaginatedInCategory(
        int $categoryId,
        ProductFilterData $filterData,
        string $orderingModeId,
        int $page,
        int $limit,
        bool $showUnavailableProducts = true
    ): PaginationResult {
        $paginationResult = $this->productOnCurrentDomainFacade->getPaginatedProductsInCategory($filterData, $orderingModeId, $page, $limit, $categoryId, $showUnavailableProducts);

        return $this->createPaginationResultWithArray($paginationResult);
    }
}
