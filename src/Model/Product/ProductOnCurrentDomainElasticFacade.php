<?php

declare(strict_types=1);

namespace App\Model\Product;

use App\Model\Product\Search\FilterQuery;
use Shopsys\FrameworkBundle\Component\Paginator\PaginationResult;
use Shopsys\FrameworkBundle\Model\Category\Category;
use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfig;
use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterCountData;
use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterData;
use Shopsys\FrameworkBundle\Model\Product\Listing\ProductListOrderingConfig;
use Shopsys\FrameworkBundle\Model\Product\ProductOnCurrentDomainElasticFacade as BaseProductOnCurrentDomainElasticFacade;
use Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery as BaseFilterQuery;

/**
 * @property \App\Model\Product\Search\FilterQueryFactory $filterQueryFactory
 * @property \App\Model\Product\ProductRepository $productRepository
 * @method \App\Model\Product\Product getVisibleProductById(int $productId)
 * @method \App\Model\Product\Product[] getAccessoriesForProduct(\App\Model\Product\Product $product)
 * @method \App\Model\Product\Product[] getVariantsForProduct(\App\Model\Product\Product $product)
 */
class ProductOnCurrentDomainElasticFacade extends BaseProductOnCurrentDomainElasticFacade
{
    /**
     * @param int[] $ids
     * @return array
     */
    public function getSellableHitsForIds(array $ids): array
    {
        $filterQuery = $this->filterQueryFactory->create($this->getIndexName())
            ->filterIds(array_values($ids));

        return $this->productElasticsearchRepository->getSortedProductsResultByFilterQuery($filterQuery)->getHits();
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterData $productFilterData
     * @param string $orderingModeId
     * @param int $page
     * @param int $limit
     * @param int $pohodaProductType
     * @return \App\Model\Product\Search\FilterQuery
     */
    protected function createFilterQueryWithProductFilterData(
        ProductFilterData $productFilterData,
        string $orderingModeId,
        int $page,
        int $limit,
        ?int $pohodaProductType = Product::POHODA_PRODUCT_TYPE_ID_SINGLE_PRODUCT
    ): BaseFilterQuery {
        $filterQuery = $this->filterQueryFactory->create($this->getIndexName())
            ->filterOnlyVisible($this->currentCustomerUser->getPricingGroup());

        if ($pohodaProductType !== null) {
            $filterQuery = $filterQuery->filterByPohodaProductType($pohodaProductType);
        }

        $filterQuery = $filterQuery
            ->setPage($page)
            ->setLimit($limit)
            ->applyOrdering($orderingModeId, $this->currentCustomerUser->getPricingGroup());

        $filterQuery = $this->productFilterDataToQueryTransformer->addBrandsToQuery($productFilterData, $filterQuery);
        $filterQuery = $this->productFilterDataToQueryTransformer->addFlagsToQuery($productFilterData, $filterQuery);
        $filterQuery = $this->productFilterDataToQueryTransformer->addParametersToQuery($productFilterData, $filterQuery);
        $filterQuery = $this->productFilterDataToQueryTransformer->addStockToQuery($productFilterData, $filterQuery);
        $filterQuery = $this->productFilterDataToQueryTransformer->addPricesToQuery($productFilterData, $filterQuery, $this->currentCustomerUser->getPricingGroup());
        /** @var \App\Model\Product\Search\FilterQuery $filterQuery */
        return $filterQuery;
    }

    /**
     * @param string $orderingModeId
     * @param int $page
     * @param int $limit
     * @param int[] $flagIds
     * @return \Shopsys\FrameworkBundle\Component\Paginator\PaginationResult
     */
    public function getPaginatedProductsForFlags(string $orderingModeId, int $page, int $limit, array $flagIds): PaginationResult
    {
        $emptyProductFilterData = new ProductFilterData();

        $filterQuery = $this->createListableProductsForFlagFilterQuery($emptyProductFilterData, $orderingModeId, $page, $limit, $flagIds);

        $productsResult = $this->productElasticsearchRepository->getSortedProductsResultByFilterQuery($filterQuery);

        return new PaginationResult($page, $limit, $productsResult->getTotal(), $productsResult->getHits());
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterData $productFilterData
     * @param string $orderingModeId
     * @param int $page
     * @param int $limit
     * @param int[] $flagIds
     * @return \App\Model\Product\Search\FilterQuery
     */
    protected function createListableProductsForFlagFilterQuery(
        ProductFilterData $productFilterData,
        string $orderingModeId,
        int $page,
        int $limit,
        array $flagIds
    ): FilterQuery {
        return $this->createFilterQueryWithProductFilterData($productFilterData, $orderingModeId, $page, $limit)
            ->filterByFlags($flagIds);
    }

    /**
     * @param string|null $searchText
     * @param int $limit
     * @param int $pohodaProductType
     * @return \Shopsys\FrameworkBundle\Component\Paginator\PaginationResult
     */
    public function getSearchAutocompleteProducts(
        ?string $searchText,
        int $limit,
        int $pohodaProductType = Product::POHODA_PRODUCT_TYPE_ID_SINGLE_PRODUCT
    ): PaginationResult {
        $emptyProductFilterData = new ProductFilterData();
        $page = 1;

        $filterQuery = $this->createListableProductsForSearchTextFilterQuery($emptyProductFilterData, ProductListOrderingConfig::ORDER_BY_RELEVANCE, $page, $limit, $searchText, $pohodaProductType);

        $productIds = $this->productElasticsearchRepository->getSortedProductIdsByFilterQuery($filterQuery);

        $listableProductsByIds = $this->productRepository->getListableByIds($this->domain->getId(), $this->currentCustomerUser->getPricingGroup(), $productIds->getIds());

        return new PaginationResult($page, $limit, $productIds->getTotal(), $listableProductsByIds);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterData $productFilterData
     * @param string $orderingModeId
     * @param int $page
     * @param int $limit
     * @param string|null $searchText
     * @param int $pohodaProductType
     * @return \App\Model\Product\Search\FilterQuery
     */
    protected function createListableProductsForSearchTextFilterQuery(
        ProductFilterData $productFilterData,
        string $orderingModeId,
        int $page,
        int $limit,
        ?string $searchText,
        int $pohodaProductType = Product::POHODA_PRODUCT_TYPE_ID_SINGLE_PRODUCT
    ): BaseFilterQuery {
        $searchText = $searchText ?? '';

        return $this->createFilterQueryWithProductFilterData($productFilterData, $orderingModeId, $page, $limit, $pohodaProductType)
            ->search($searchText);
    }

    /**
     * @param string $searchText
     * @param \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterData $productFilterData
     * @param string $orderingModeId
     * @param int $page
     * @param int $limit
     * @param int $pohodaProductType
     * @return \Shopsys\FrameworkBundle\Component\Paginator\PaginationResult
     */
    public function getPaginatedProductsForSearch(
        string $searchText,
        ProductFilterData $productFilterData,
        string $orderingModeId,
        int $page,
        int $limit,
        int $pohodaProductType = Product::POHODA_PRODUCT_TYPE_ID_SINGLE_PRODUCT
    ): PaginationResult {
        $filterQuery = $this->createListableProductsForSearchTextFilterQuery($productFilterData, $orderingModeId, $page, $limit, $searchText, $pohodaProductType);

        $productsResult = $this->productElasticsearchRepository->getSortedProductsResultByFilterQuery($filterQuery);

        return new PaginationResult($page, $limit, $productsResult->getTotal(), $productsResult->getHits());
    }

    /**
     * Override removes product groups from filter counts
     *
     * @inheritDoc
     */
    public function getProductFilterCountDataForSearch(?string $searchText, ProductFilterConfig $productFilterConfig, ProductFilterData $productFilterData): ProductFilterCountData
    {
        $searchText = $searchText ?? '';

        $baseFilterQuery = $this->filterQueryFactory->create($this->getIndexName())
            ->filterOnlySellable()
            ->filterOnlyVisible($this->currentCustomerUser->getPricingGroup())
            ->filterByPohodaProductType(Product::POHODA_PRODUCT_TYPE_ID_SINGLE_PRODUCT)
            ->search($searchText);
        $baseFilterQuery = $this->productFilterDataToQueryTransformer->addPricesToQuery($productFilterData, $baseFilterQuery, $this->currentCustomerUser->getPricingGroup());
        $baseFilterQuery = $this->productFilterDataToQueryTransformer->addStockToQuery($productFilterData, $baseFilterQuery);

        return $this->productFilterCountDataElasticsearchRepository->getProductFilterCountDataInSearch(
            $productFilterData,
            $baseFilterQuery
        );
    }

    /**
     * Override makes product groups appear in results
     *
     * @inheritDoc
     */
    protected function createListableProductsInCategoryFilterQuery(ProductFilterData $productFilterData, string $orderingModeId, int $page, int $limit, int $categoryId, bool $showUnavailableProducts = true): BaseFilterQuery
    {
        $filterQuery = $this->createFilterQueryWithProductFilterData($productFilterData, $orderingModeId, $page, $limit, null)
            ->filterByCategory([$categoryId]);

        if (!$showUnavailableProducts) {
            $filterQuery = $filterQuery->filterOnlySellable();
        }

        return $filterQuery;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterData $productFilterData
     * @param string $orderingModeId
     * @param int $page
     * @param int $limit
     * @param int $categoryId
     * @param bool $showUnavailableProducts
     * @return \Shopsys\FrameworkBundle\Component\Paginator\PaginationResult
     */
    public function getPaginatedProductsInCategory(
        ProductFilterData $productFilterData,
        string $orderingModeId,
        int $page,
        int $limit,
        int $categoryId,
        bool $showUnavailableProducts = true
    ): PaginationResult {
        $filterQuery = $this->createListableProductsInCategoryFilterQuery($productFilterData, $orderingModeId, $page, $limit, $categoryId, $showUnavailableProducts);

        $productsResult = $this->productElasticsearchRepository->getSortedProductsResultByFilterQuery($filterQuery);

        return new PaginationResult($page, $limit, $productsResult->getTotal(), $productsResult->getHits());
    }

    /**
     * @param \App\Model\Category\Category $category
     * @param int $limit
     * @param int $offset
     * @param string $orderingModeId
     * @return array
     */
    public function getProductsByCategory(Category $category, int $limit, int $offset, string $orderingModeId): array
    {
        $emptyProductFilterData = new ProductFilterData();
        $filterQuery = $this->createListableProductsInCategoryFilterQuery(
            $emptyProductFilterData,
            $orderingModeId,
            1,
            $limit,
            $category->getId(),
            $category->isUnavailableProductsShown()
        )->setFrom($offset);

        $productsResult = $this->productElasticsearchRepository->getSortedProductsResultByFilterQuery($filterQuery);

        return $productsResult->getHits();
    }
}
