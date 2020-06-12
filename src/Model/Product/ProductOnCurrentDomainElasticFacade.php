<?php

declare(strict_types=1);

namespace App\Model\Product;

use App\Model\Product\Search\FilterQuery;
use Shopsys\FrameworkBundle\Component\Paginator\PaginationResult;
use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterData;
use Shopsys\FrameworkBundle\Model\Product\ProductOnCurrentDomainElasticFacade as BaseProductOnCurrentDomainElasticFacade;
use Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery as BaseFilterQuery;

/**
 * @property \App\Model\Product\Search\FilterQueryFactory $filterQueryFactory
 * @property \App\Model\Product\ProductRepository $productRepository
 * @method \App\Model\Product\Product getVisibleProductById(int $productId)
 * @method \App\Model\Product\Product[] getAccessoriesForProduct(\App\Model\Product\Product $product)
 * @method \App\Model\Product\Product[] getVariantsForProduct(\App\Model\Product\Product $product)
 * @method array getProductsByCategory(\App\Model\Category\Category $category, int $limit, int $offset, string $orderingModeId)
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
     * @return \App\Model\Product\Search\FilterQuery
     */
    protected function createFilterQueryWithProductFilterData(
        ProductFilterData $productFilterData,
        string $orderingModeId,
        int $page,
        int $limit
    ): BaseFilterQuery {
        $filterQuery = $this->filterQueryFactory->create($this->getIndexName())
            ->filterOnlyVisible($this->currentCustomerUser->getPricingGroup())
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
}
