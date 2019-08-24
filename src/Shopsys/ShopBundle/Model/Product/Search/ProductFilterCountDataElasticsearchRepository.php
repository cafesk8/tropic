<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Search;

use Elasticsearch\Client;
use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterCountData;
use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterData;
use Shopsys\FrameworkBundle\Model\Product\Search\AggregationResultToProductFilterCountDataTransformer;
use Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery;
use Shopsys\FrameworkBundle\Model\Product\Search\ProductFilterCountDataElasticsearchRepository as BaseProductFilterCountDataElasticsearchRepository;
use Shopsys\FrameworkBundle\Model\Product\Search\ProductFilterDataToQueryTransformer;
use Shopsys\ShopBundle\Model\Product\Parameter\Parameter;
use Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade;

class ProductFilterCountDataElasticsearchRepository extends BaseProductFilterCountDataElasticsearchRepository
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade
     */
    private $parameterFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Search\ProductFilterDataToQueryTransformer
     */
    protected $productFilterDataToQueryTransformer;

    /**
     * @param \Elasticsearch\Client $client
     * @param \Shopsys\FrameworkBundle\Model\Product\Search\ProductFilterDataToQueryTransformer $productFilterDataToQueryTransformer
     * @param \Shopsys\FrameworkBundle\Model\Product\Search\AggregationResultToProductFilterCountDataTransformer $aggregationResultToCountDataTransformer
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade $parameterFacade
     */
    public function __construct(
        Client $client,
        ProductFilterDataToQueryTransformer $productFilterDataToQueryTransformer,
        AggregationResultToProductFilterCountDataTransformer $aggregationResultToCountDataTransformer,
        ParameterFacade $parameterFacade
    ) {
        parent::__construct($client, $productFilterDataToQueryTransformer, $aggregationResultToCountDataTransformer);
        $this->parameterFacade = $parameterFacade;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Filter\ProductFilterData $productFilterData
     * @param \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery $baseFilterQuery
     * @return \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterCountData
     */
    public function getProductFilterCountDataInCategory(ProductFilterData $productFilterData, FilterQuery $baseFilterQuery): ProductFilterCountData
    {
        $absoluteNumbersFilterQuery = $this->productFilterDataToQueryTransformer->addFlagsToQuery($productFilterData, $baseFilterQuery);
        $absoluteNumbersFilterQuery = $this->productFilterDataToQueryTransformer->addBrandsToQuery($productFilterData, $absoluteNumbersFilterQuery);

        $distinguishingParameters = [
            Parameter::TYPE_COLOR => $this->parameterFacade->getColorParameter(),
            Parameter::TYPE_SIZE => $this->parameterFacade->getSizeParameter(),
        ];

        $absoluteNumbersFilterQuery = $this->productFilterDataToQueryTransformer->addParametersToQuery($productFilterData, $absoluteNumbersFilterQuery);
        $absoluteNumbersFilterQuery = $this->productFilterDataToQueryTransformer->addDistinguishingParametersToQuery($productFilterData, $absoluteNumbersFilterQuery, $distinguishingParameters);

        $aggregationResult = $this->client->search($absoluteNumbersFilterQuery->getAbsoluteNumbersWithParametersQuery());
        $countData = $this->aggregationResultToCountDataTransformer->translateAbsoluteNumbersWithParameters($aggregationResult);

        if (count($productFilterData->flags) > 0) {
            $plusFlagsQuery = $this->productFilterDataToQueryTransformer->addBrandsToQuery($productFilterData, $baseFilterQuery);
            $plusFlagsQuery = $this->productFilterDataToQueryTransformer->addParametersToQuery($productFilterData, $plusFlagsQuery);
            $plusFlagsQuery = $this->productFilterDataToQueryTransformer->addDistinguishingParametersToQuery($productFilterData, $plusFlagsQuery, $distinguishingParameters);
            $countData->countByFlagId = $this->calculateFlagsPlusNumbers($productFilterData, $plusFlagsQuery);
        }

        if (count($productFilterData->brands) > 0) {
            $plusBrandsQuery = $this->productFilterDataToQueryTransformer->addFlagsToQuery($productFilterData, $baseFilterQuery);
            $plusBrandsQuery = $this->productFilterDataToQueryTransformer->addParametersToQuery($productFilterData, $plusBrandsQuery);
            $plusBrandsQuery = $this->productFilterDataToQueryTransformer->addDistinguishingParametersToQuery($productFilterData, $plusBrandsQuery, $distinguishingParameters);
            $countData->countByBrandId = $this->calculateBrandsPlusNumbers($productFilterData, $plusBrandsQuery);
        }

        if (count($productFilterData->parameters) > 0) {
            $plusParametersQuery = $this->productFilterDataToQueryTransformer->addFlagsToQuery($productFilterData, $baseFilterQuery);
            $plusParametersQuery = $this->productFilterDataToQueryTransformer->addBrandsToQuery($productFilterData, $plusParametersQuery);
            $plusParametersQuery = $this->productFilterDataToQueryTransformer->addDistinguishingParametersToQuery($productFilterData, $plusParametersQuery, $distinguishingParameters);

            $this->replaceParametersPlusNumbers($productFilterData, $countData, $plusParametersQuery);
        }

        return $countData;
    }
}
