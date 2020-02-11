<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Search;

use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterData;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValue;
use Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery;
use Shopsys\FrameworkBundle\Model\Product\Search\ProductFilterDataToQueryTransformer as BaseProductFilterDataToQueryTransformer;
use Shopsys\ShopBundle\Model\Product\Parameter\Parameter;

/**
 * @method \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery addBrandsToQuery(\Shopsys\ShopBundle\Model\Product\Filter\ProductFilterData $productFilterData, \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery $filterQuery)
 * @method \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery addFlagsToQuery(\Shopsys\ShopBundle\Model\Product\Filter\ProductFilterData $productFilterData, \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery $filterQuery)
 * @method \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery addParametersToQuery(\Shopsys\ShopBundle\Model\Product\Filter\ProductFilterData $productFilterData, \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery $filterQuery)
 * @method \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery addStockToQuery(\Shopsys\ShopBundle\Model\Product\Filter\ProductFilterData $productFilterData, \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery $filterQuery)
 * @method \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery addPricesToQuery(\Shopsys\ShopBundle\Model\Product\Filter\ProductFilterData $productFilterData, \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery $filterQuery, \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup $pricingGroup)
 */
class ProductFilterDataToQueryTransformer extends BaseProductFilterDataToQueryTransformer
{
    /**
     * @param \Shopsys\ShopBundle\Model\Product\Filter\ProductFilterData $productFilterData
     * @param \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery $filterQuery
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\Parameter[] $distinguishingParameters
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
