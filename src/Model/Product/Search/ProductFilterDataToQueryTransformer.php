<?php

declare(strict_types=1);

namespace App\Model\Product\Search;

use App\Model\Product\Filter\ProductFilterData;
use Shopsys\FrameworkBundle\Model\Product\Search\ProductFilterDataToQueryTransformer as BaseProductFilterDataToQueryTransformer;

/**
 * @method \App\Model\Product\Search\FilterQuery addBrandsToQuery(\App\Model\Product\Filter\ProductFilterData $productFilterData, \App\Model\Product\Search\FilterQuery $filterQuery)
 * @method \App\Model\Product\Search\FilterQuery addFlagsToQuery(\App\Model\Product\Filter\ProductFilterData $productFilterData, \App\Model\Product\Search\FilterQuery $filterQuery)
 * @method \App\Model\Product\Search\FilterQuery addParametersToQuery(\App\Model\Product\Filter\ProductFilterData $productFilterData, \App\Model\Product\Search\FilterQuery $filterQuery)
 * @method \App\Model\Product\Search\FilterQuery addStockToQuery(\App\Model\Product\Filter\ProductFilterData $productFilterData, \App\Model\Product\Search\FilterQuery $filterQuery)
 * @method \App\Model\Product\Search\FilterQuery addPricesToQuery(\App\Model\Product\Filter\ProductFilterData $productFilterData, \App\Model\Product\Search\FilterQuery $filterQuery, \App\Model\Pricing\Group\PricingGroup $pricingGroup)
 */
class ProductFilterDataToQueryTransformer extends BaseProductFilterDataToQueryTransformer
{
    /**
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @param \App\Model\Product\Search\FilterQuery $filterQuery
     * @return \App\Model\Product\Search\FilterQuery
     */
    public function addAvailabilityToQuery(ProductFilterData $productFilterData, FilterQuery $filterQuery): FilterQuery
    {
        if ($productFilterData->available === false) {
            return $filterQuery;
        }

        return $filterQuery->filterOnlyAvailable();
    }
}
