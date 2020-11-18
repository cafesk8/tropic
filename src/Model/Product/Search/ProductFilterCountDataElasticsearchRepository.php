<?php

declare(strict_types=1);

namespace App\Model\Product\Search;

use App\Model\Product\Filter\ProductFilterCountData;
use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterCountData as BaseProductFilterCountData;
use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterData as BaseProductFilterData;
use Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery;
use Shopsys\FrameworkBundle\Model\Product\Search\ProductFilterCountDataElasticsearchRepository as BaseProductFilterCountDataElasticsearchRepository;

/**
 * @method int[] calculateFlagsPlusNumbers(\App\Model\Product\Filter\ProductFilterData $productFilterData, \App\Model\Product\Search\FilterQuery $plusFlagsQuery)
 * @method int[] calculateBrandsPlusNumbers(\App\Model\Product\Filter\ProductFilterData $productFilterData, \App\Model\Product\Search\FilterQuery $plusFlagsQuery)
 * @method replaceParametersPlusNumbers(\App\Model\Product\Filter\ProductFilterData $productFilterData, \App\Model\Product\Filter\ProductFilterCountData $countData, \App\Model\Product\Search\FilterQuery $plusParametersQuery)
 * @method array calculateParameterPlusNumbers(\Shopsys\FrameworkBundle\Model\Product\Filter\ParameterFilterData $parameterFilterData, \App\Model\Product\Search\FilterQuery $parameterFilterQuery)
 * @property \App\Model\Product\Search\AggregationResultToProductFilterCountDataTransformer $aggregationResultToCountDataTransformer
 * @method __construct(\Elasticsearch\Client $client, \App\Model\Product\Search\ProductFilterDataToQueryTransformer $productFilterDataToQueryTransformer, \App\Model\Product\Search\AggregationResultToProductFilterCountDataTransformer $aggregationResultToCountDataTransformer)
 * @method mergeParameterCountData(\App\Model\Product\Filter\ProductFilterCountData $countData, array $plusParameterNumbers, int $parameterId)
 * @property \App\Model\Product\Search\ProductFilterDataToQueryTransformer $productFilterDataToQueryTransformer
 */
class ProductFilterCountDataElasticsearchRepository extends BaseProductFilterCountDataElasticsearchRepository
{
    /**
     * Counts for filters are calculated this way:
     * for parameters, main variants are excluded,
     * for brands and flags, variants are excluded
     *
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @param \App\Model\Product\Search\FilterQuery $baseFilterQuery
     * @return \App\Model\Product\Filter\ProductFilterCountData
     */
    public function getProductFilterCountDataInSearch(BaseProductFilterData $productFilterData, FilterQuery $baseFilterQuery): BaseProductFilterCountData
    {
        $baseFilterQueryExcludeVariants = $baseFilterQuery->excludeVariants();
        $absoluteNumbersFilterQuery = $this->productFilterDataToQueryTransformer->addFlagsToQuery($productFilterData, $baseFilterQueryExcludeVariants);
        $absoluteNumbersFilterQuery = $this->productFilterDataToQueryTransformer->addBrandsToQuery($productFilterData, $absoluteNumbersFilterQuery);

        $aggregationResult = $this->client->search($absoluteNumbersFilterQuery->getAbsoluteNumbersAggregationQuery());
        $countData = $this->aggregationResultToCountDataTransformer->translateAbsoluteNumbers($aggregationResult);

        if (count($productFilterData->flags) > 0) {
            $plusFlagsQuery = $this->productFilterDataToQueryTransformer->addBrandsToQuery($productFilterData, $baseFilterQueryExcludeVariants);
            $countData->countByFlagId = $this->calculateFlagsPlusNumbers($productFilterData, $plusFlagsQuery);
        }

        if (count($productFilterData->brands) > 0) {
            $plusBrandsQuery = $this->productFilterDataToQueryTransformer->addFlagsToQuery($productFilterData, $baseFilterQueryExcludeVariants);
            $countData->countByBrandId = $this->calculateBrandsPlusNumbers($productFilterData, $plusBrandsQuery);
        }

        return $countData;
    }

    /**
     * Counts for filters are calculated this way:
     * for parameters, main variants are excluded,
     * for brands and flags, variants are excluded
     *
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @param \App\Model\Product\Search\FilterQuery $baseFilterQuery
     * @return \App\Model\Product\Filter\ProductFilterCountData
     */
    public function getProductFilterCountDataInCategory(BaseProductFilterData $productFilterData, FilterQuery $baseFilterQuery): BaseProductFilterCountData
    {
        $baseFilterQueryExcludeVariants = $baseFilterQuery->excludeVariants();
        $baseFilterQueryExcludeMainVariants = $baseFilterQuery->excludeMainVariants();

        $absoluteNumbersFilterQueryExcludeVariants = $this->productFilterDataToQueryTransformer->addFlagsToQuery($productFilterData, $baseFilterQueryExcludeVariants);
        $absoluteNumbersFilterQueryExcludeVariants = $this->productFilterDataToQueryTransformer->addBrandsToQuery($productFilterData, $absoluteNumbersFilterQueryExcludeVariants);
        $absoluteNumbersFilterQueryExcludeVariants = $this->productFilterDataToQueryTransformer->addParametersToQuery($productFilterData, $absoluteNumbersFilterQueryExcludeVariants);

        $absoluteNumbersFilterQueryExcludeMainVariants = $this->productFilterDataToQueryTransformer->addFlagsToQuery($productFilterData, $baseFilterQueryExcludeMainVariants);
        $absoluteNumbersFilterQueryExcludeMainVariants = $this->productFilterDataToQueryTransformer->addBrandsToQuery($productFilterData, $absoluteNumbersFilterQueryExcludeMainVariants);
        $absoluteNumbersFilterQueryExcludeMainVariants = $this->productFilterDataToQueryTransformer->addParametersToQuery($productFilterData, $absoluteNumbersFilterQueryExcludeMainVariants);

        $aggregationResultExcludeVariants = $this->client->search($absoluteNumbersFilterQueryExcludeVariants->getAbsoluteNumbersWithParametersQuery());
        $aggregationResultExcludeMainVariants = $this->client->search($absoluteNumbersFilterQueryExcludeMainVariants->getAbsoluteNumbersWithParametersQuery());

        $countDataExcludeVariants = $this->aggregationResultToCountDataTransformer->translateAbsoluteNumbersWithParameters($aggregationResultExcludeVariants);
        $countDataExcludeMainVariants = $this->aggregationResultToCountDataTransformer->translateAbsoluteNumbersWithParameters($aggregationResultExcludeMainVariants);

        $countData = new ProductFilterCountData();
        $countData->countByFlagId = $countDataExcludeVariants->countByFlagId;
        $countData->countByBrandId = $countDataExcludeVariants->countByBrandId;
        $countData->countByParameterIdAndValueId = $countDataExcludeMainVariants->countByParameterIdAndValueId;
        $countData->countInStock = $countDataExcludeMainVariants->countInStock;
        $countData->countAvailable = $countDataExcludeMainVariants->countAvailable;

        if (count($productFilterData->flags) > 0) {
            $plusFlagsQuery = $this->productFilterDataToQueryTransformer->addBrandsToQuery($productFilterData, $baseFilterQueryExcludeVariants);
            $plusFlagsQuery = $this->productFilterDataToQueryTransformer->addParametersToQuery($productFilterData, $plusFlagsQuery);
            $countData->countByFlagId = $this->calculateFlagsPlusNumbers($productFilterData, $plusFlagsQuery);
        }

        if (count($productFilterData->brands) > 0) {
            $plusBrandsQuery = $this->productFilterDataToQueryTransformer->addFlagsToQuery($productFilterData, $baseFilterQueryExcludeVariants);
            $plusBrandsQuery = $this->productFilterDataToQueryTransformer->addParametersToQuery($productFilterData, $plusBrandsQuery);
            $countData->countByBrandId = $this->calculateBrandsPlusNumbers($productFilterData, $plusBrandsQuery);
        }

        if (count($productFilterData->parameters) > 0) {
            $plusParametersQuery = $this->productFilterDataToQueryTransformer->addFlagsToQuery($productFilterData, $baseFilterQueryExcludeMainVariants);
            $plusParametersQuery = $this->productFilterDataToQueryTransformer->addBrandsToQuery($productFilterData, $plusParametersQuery);

            $this->replaceParametersPlusNumbers($productFilterData, $countData, $plusParametersQuery);
        }

        return $countData;
    }
}
