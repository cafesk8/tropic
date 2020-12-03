<?php

declare(strict_types=1);

namespace App\Model\Product\Search;

use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterCountData as BaseProductFilterCountData;
use Shopsys\FrameworkBundle\Model\Product\Search\AggregationResultToProductFilterCountDataTransformer as BaseAggregationResultToProductFilterCountDataTransformer;

/**
 * @method \App\Model\Product\Filter\ProductFilterCountData translateAbsoluteNumbersWithParameters(array $aggregationResult)
 */
class AggregationResultToProductFilterCountDataTransformer extends BaseAggregationResultToProductFilterCountDataTransformer
{
    /**
     * @param array $aggregationResult
     * @return \App\Model\Product\Filter\ProductFilterCountData
     */
    public function translateAbsoluteNumbers(array $aggregationResult): BaseProductFilterCountData
    {
        /** @var \App\Model\Product\Filter\ProductFilterCountData $countData */
        $countData = parent::translateAbsoluteNumbers($aggregationResult);
        $countData->countAvailable = $this->getAvailableCount($aggregationResult);

        return $countData;
    }

    /**
     * @param array $aggregationResult
     * @return int
     */
    private function getAvailableCount(array $aggregationResult): int
    {
        return $aggregationResult['aggregations']['available']['doc_count'];
    }
}
