<?php

declare(strict_types=1);

namespace App\Model\Product;

use App\Model\Product\Filter\ProductFilterData;
use App\Model\Product\Search\FilterQuery;
use Shopsys\FrameworkBundle\Component\Paginator\PaginationResult;
use Shopsys\FrameworkBundle\Model\Category\Category;
use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfig;
use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterCountData;
use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterData as BaseProductFilterData;
use Shopsys\FrameworkBundle\Model\Product\ProductOnCurrentDomainElasticFacade as BaseProductOnCurrentDomainElasticFacade;
use Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery as BaseFilterQuery;

/**
 * @property \App\Model\Product\Search\FilterQueryFactory $filterQueryFactory
 * @property \App\Model\Product\ProductRepository $productRepository
 * @property \App\Model\Product\Search\ProductFilterDataToQueryTransformer $productFilterDataToQueryTransformer
 * @property \App\Model\Product\Search\ProductFilterCountDataElasticsearchRepository $productFilterCountDataElasticsearchRepository
 * @method \App\Model\Product\Product getVisibleProductById(int $productId)
 * @method \App\Model\Product\Product[] getAccessoriesForProduct(\App\Model\Product\Product $product)
 * @method \App\Model\Product\Product[] getVariantsForProduct(\App\Model\Product\Product $product)
 */
class ProductOnCurrentDomainElasticFacade extends BaseProductOnCurrentDomainElasticFacade
{
    /**
     * @param int[] $ids
     * @param string|null $routeName
     * @param bool $onlyAvailable
     * @return array
     */
    public function getSellableHitsForIds(array $ids, ?string $routeName = null, bool $onlyAvailable = false): array
    {
        $filterQuery = $this->filterQueryFactory->create($this->getIndexName())
            ->filterIds(array_values($ids));

        if ($routeName === 'front_sale_product_list') {
            $filterQuery = $filterQuery->filterOnlyInSale();
        }

        if ($routeName === 'front_news_product_list') {
            $filterQuery = $filterQuery->filterOnlyInNews();
        }

        if ($onlyAvailable) {
            $filterQuery = $filterQuery->filterOnlyAvailable();
        }

        $hits = $this->productElasticsearchRepository->getSortedProductsResultByFilterQuery($filterQuery)->getHits();

        return $this->sortByOriginalArray($hits, $ids);
    }

    /**
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @param string $orderingModeId
     * @param int $page
     * @param int $limit
     * @param int|null $pohodaProductType
     * @param bool $includeGiftCards
     * @return \App\Model\Product\Search\FilterQuery
     */
    protected function createFilterQueryWithProductFilterData(
        BaseProductFilterData $productFilterData,
        string $orderingModeId,
        int $page,
        int $limit,
        ?int $pohodaProductType = Product::POHODA_PRODUCT_TYPE_ID_SINGLE_PRODUCT,
        bool $includeGiftCards = true
    ): BaseFilterQuery {
        $filterQuery = $this->filterQueryFactory->create($this->getIndexName())
            ->filterOnlyVisible($this->currentCustomerUser->getPricingGroup())
            ->excludeVariants();

        if ($pohodaProductType !== null) {
            if ($includeGiftCards) {
                $filterQuery = $filterQuery->filterByPohodaProductAndGiftCardType($pohodaProductType, Product::POHODA_PRODUCT_TYPE_ID_GIFT_CARD);
            } else {
                $filterQuery = $filterQuery->filterByPohodaProductType($pohodaProductType);
            }
        }

        $filterQuery = $filterQuery
            ->setPage($page)
            ->setLimit($limit)
            ->applyOrdering($orderingModeId, $this->currentCustomerUser->getPricingGroup());

        $filterQuery = $this->productFilterDataToQueryTransformer->addBrandsToQuery($productFilterData, $filterQuery);
        $filterQuery = $this->productFilterDataToQueryTransformer->addFlagsToQuery($productFilterData, $filterQuery);
        $filterQuery = $this->productFilterDataToQueryTransformer->addParametersToQuery($productFilterData, $filterQuery);
        $filterQuery = $this->productFilterDataToQueryTransformer->addStockToQuery($productFilterData, $filterQuery);
        $filterQuery = $this->productFilterDataToQueryTransformer->addAvailabilityToQuery($productFilterData, $filterQuery);
        $filterQuery = $this->productFilterDataToQueryTransformer->addPricesToQuery($productFilterData, $filterQuery, $this->currentCustomerUser->getPricingGroup());

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

        $filterQuery = $this->createListableProductsForFlagFilterQuery($emptyProductFilterData, $orderingModeId, $page, $limit, $flagIds)
            ->filterOnlyAvailable();

        $productsResult = $this->productElasticsearchRepository->getSortedProductsResultByFilterQuery($filterQuery);

        return new PaginationResult($page, $limit, $productsResult->getTotal(), $productsResult->getHits());
    }

    /**
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
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
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @param string $orderingModeId
     * @param int $page
     * @param int $limit
     * @param string|null $searchText
     * @param int $pohodaProductType
     * @param bool $includeGiftCards
     * @return \App\Model\Product\Search\FilterQuery
     */
    protected function createListableProductsForSearchTextFilterQuery(
        BaseProductFilterData $productFilterData,
        string $orderingModeId,
        int $page,
        int $limit,
        ?string $searchText,
        int $pohodaProductType = Product::POHODA_PRODUCT_TYPE_ID_SINGLE_PRODUCT,
        bool $includeGiftCards = true
    ): BaseFilterQuery {
        $searchText = $searchText ?? '';

        return $this->createFilterQueryWithProductFilterData($productFilterData, $orderingModeId, $page, $limit, $pohodaProductType, $includeGiftCards)
            ->search($searchText);
    }

    /**
     * @param string $searchText
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @param string $orderingModeId
     * @param int $page
     * @param int $limit
     * @param int $pohodaProductType
     * @param bool $includeGiftCards
     * @return \Shopsys\FrameworkBundle\Component\Paginator\PaginationResult
     */
    public function getPaginatedProductsForSearch(
        string $searchText,
        BaseProductFilterData $productFilterData,
        string $orderingModeId,
        int $page,
        int $limit,
        int $pohodaProductType = Product::POHODA_PRODUCT_TYPE_ID_SINGLE_PRODUCT,
        bool $includeGiftCards = true
    ): PaginationResult {
        $filterQuery = $this->createListableProductsForSearchTextFilterQuery($productFilterData, $orderingModeId, $page, $limit, $searchText, $pohodaProductType, $includeGiftCards);

        $productsResult = $this->productElasticsearchRepository->getSortedProductsResultByFilterQuery($filterQuery);

        return new PaginationResult($page, $limit, $productsResult->getTotal(), $productsResult->getHits());
    }

    /**
     * Includes visible not-sellable products (when the category is set to show unavailable products)
     *
     * @inheritDoc
     */
    public function getProductFilterCountDataInCategory(int $categoryId, ProductFilterConfig $productFilterConfig, BaseProductFilterData $productFilterData, bool $showUnavailableProducts = false): ProductFilterCountData
    {
        $baseFilterQuery = $this->filterQueryFactory->create($this->getIndexName())
            ->filterOnlyVisible($this->currentCustomerUser->getPricingGroup())
            ->filterByCategory([$categoryId]);
        if (!$showUnavailableProducts) {
            $baseFilterQuery = $baseFilterQuery->filterOnlySellable();
        }

        $baseFilterQuery = $this->productFilterDataToQueryTransformer->addPricesToQuery($productFilterData, $baseFilterQuery, $this->currentCustomerUser->getPricingGroup());
        $baseFilterQuery = $this->productFilterDataToQueryTransformer->addStockToQuery($productFilterData, $baseFilterQuery);
        $baseFilterQuery = $this->productFilterDataToQueryTransformer->addAvailabilityToQuery($productFilterData, $baseFilterQuery);

        return $this->productFilterCountDataElasticsearchRepository->getProductFilterCountDataInCategory(
            $productFilterData,
            $baseFilterQuery
        );
    }

    /**
     * Override removes product groups from filter counts
     * Includes visible not-sellable products
     *
     * @inheritDoc
     */
    public function getProductFilterCountDataForSearch(?string $searchText, ProductFilterConfig $productFilterConfig, BaseProductFilterData $productFilterData): ProductFilterCountData
    {
        $searchText = $searchText ?? '';

        $baseFilterQuery = $this->filterQueryFactory->create($this->getIndexName())
            ->filterOnlyVisible($this->currentCustomerUser->getPricingGroup())
            ->filterByPohodaProductType(Product::POHODA_PRODUCT_TYPE_ID_SINGLE_PRODUCT)
            ->search($searchText);
        $baseFilterQuery = $this->productFilterDataToQueryTransformer->addPricesToQuery($productFilterData, $baseFilterQuery, $this->currentCustomerUser->getPricingGroup());
        $baseFilterQuery = $this->productFilterDataToQueryTransformer->addStockToQuery($productFilterData, $baseFilterQuery);
        $baseFilterQuery = $this->productFilterDataToQueryTransformer->addAvailabilityToQuery($productFilterData, $baseFilterQuery);

        return $this->productFilterCountDataElasticsearchRepository->getProductFilterCountDataInSearch(
            $productFilterData,
            $baseFilterQuery
        );
    }

    /**
     * Override makes product sets appear in results
     *
     * @inheritDoc
     */
    protected function createListableProductsInCategoryFilterQuery(BaseProductFilterData $productFilterData, string $orderingModeId, int $page, int $limit, int $categoryId, bool $showUnavailableProducts = true): BaseFilterQuery
    {
        $filterQuery = $this->createFilterQueryWithProductFilterData($productFilterData, $orderingModeId, $page, $limit, null)
            ->filterByCategory([$categoryId]);

        if (!$showUnavailableProducts) {
            $filterQuery = $filterQuery->filterOnlySellable();
        }

        return $filterQuery;
    }

    /**
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @param string $orderingModeId
     * @param int $page
     * @param int $limit
     * @param int $categoryId
     * @param bool $showUnavailableProducts
     * @return \Shopsys\FrameworkBundle\Component\Paginator\PaginationResult
     */
    public function getPaginatedProductsInCategory(
        BaseProductFilterData $productFilterData,
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

    /**
     * @param int[] $ids
     * @return array
     */
    public function getHitsForIds(array $ids): array
    {
        $filterQuery = $this->filterQueryFactory->create($this->getIndexName())->filterIds(array_values($ids));

        $hits = $this->productElasticsearchRepository->getSortedProductsResultByFilterQuery($filterQuery)->getHits();

        return $this->sortByOriginalArray($hits, $ids);
    }

    /**
     * @param array $arrayForSorting
     * @param array $originalArray
     * @return array
     */
    private function sortByOriginalArray(array $arrayForSorting, array $originalArray): array
    {
        $result = [];
        foreach ($arrayForSorting as $item) {
            $originalIndex = array_search($item['id'], $originalArray, true);
            if ($originalIndex !== false) {
                $result[$originalIndex] = $item;
            }
        }
        ksort($result);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaginatedProductsForBrand(string $orderingModeId, int $page, int $limit, int $brandId): PaginationResult
    {
        $emptyProductFilterData = new ProductFilterData();

        $filterQuery = $this->createListableProductsForBrandFilterQuery($emptyProductFilterData, $orderingModeId, $page, $limit, $brandId);

        $productsResult = $this->productElasticsearchRepository->getSortedProductsResultByFilterQuery($filterQuery);

        return new PaginationResult($page, $limit, $productsResult->getTotal(), $productsResult->getHits());
    }
}
