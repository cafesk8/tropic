<?php

declare(strict_types=1);

namespace App\Model\Product\Search;

use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterData;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValue;
use Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery;
use Shopsys\FrameworkBundle\Model\Product\Search\ProductFilterDataToQueryTransformer as BaseProductFilterDataToQueryTransformer;
use App\Model\Product\Parameter\Parameter;

/**
 * @method \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery addBrandsToQuery(\App\Model\Product\Filter\ProductFilterData $productFilterData, \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery $filterQuery)
 * @method \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery addFlagsToQuery(\App\Model\Product\Filter\ProductFilterData $productFilterData, \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery $filterQuery)
 * @method \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery addParametersToQuery(\App\Model\Product\Filter\ProductFilterData $productFilterData, \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery $filterQuery)
 * @method \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery addStockToQuery(\App\Model\Product\Filter\ProductFilterData $productFilterData, \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery $filterQuery)
 * @method \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery addPricesToQuery(\App\Model\Product\Filter\ProductFilterData $productFilterData, \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery $filterQuery, \App\Model\Pricing\Group\PricingGroup $pricingGroup)
 */
class ProductFilterDataToQueryTransformer extends BaseProductFilterDataToQueryTransformer
{
    /**
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @param \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery $filterQuery
     * @param \App\Model\Product\Parameter\Parameter[] $distinguishingParameters
     * @return \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery
     */
    public function addDistinguishingParametersToQuery(ProductFilterData $productFilterData, FilterQuery $filterQuery, array $distinguishingParameters): FilterQuery
    {
        if (count($productFilterData->colors) === 0 && count($productFilterData->sizes) === 0) {
            return $filterQuery;
        }

        $parameters = $this->flattenParameterFilterData($productFilterData->parameters);

        if (count($productFilterData->colors) !== 0) {
            $parameters[$distinguishingParameters[Parameter::TYPE_COLOR]->getId()] = array_map(
                static function (ParameterValue $item) {
                    return $item->getId();
                },
                $productFilterData->colors
            );
        }

        if (count($productFilterData->sizes) !== 0) {
            $parameters[$distinguishingParameters[Parameter::TYPE_SIZE]->getId()] = array_map(
                static function (ParameterValue $item) {
                    return $item->getId();
                },
                $productFilterData->sizes
            );
        }

        return $filterQuery->filterByParameters($parameters);
    }
}
