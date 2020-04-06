<?php

declare(strict_types=1);

namespace App\Model\Product\Search;

use App\Model\Product\Listing\ProductListOrderingConfig;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery as BaseFilterQuery;

/**
 * @method \App\Model\Product\Search\FilterQuery filterByParameters(array $parameters)
 * @method \App\Model\Product\Search\FilterQuery filterByPrices(\App\Model\Pricing\Group\PricingGroup $pricingGroup, \Shopsys\FrameworkBundle\Component\Money\Money|null $minimalPrice, \Shopsys\FrameworkBundle\Component\Money\Money|null $maximalPrice)
 * @method \App\Model\Product\Search\FilterQuery filterByCategory(int[] $categoryIds)
 * @method \App\Model\Product\Search\FilterQuery filterByBrands(int[] $brandIds)
 * @method \App\Model\Product\Search\FilterQuery filterByFlags(int[] $flagIds)
 * @method \App\Model\Product\Search\FilterQuery filterOnlyInStock()
 * @method \App\Model\Product\Search\FilterQuery filterOnlySellable()
 * @method \App\Model\Product\Search\FilterQuery filterOnlyVisible(\App\Model\Pricing\Group\PricingGroup $pricingGroup)
 * @method \App\Model\Product\Search\FilterQuery setPage(int $page)
 * @method \App\Model\Product\Search\FilterQuery setLimit(int $limit)
 * @method \App\Model\Product\Search\FilterQuery setFrom(int $from)
 */
class FilterQuery extends BaseFilterQuery
{
    /**
     * @param string $text
     * @return \App\Model\Product\Search\FilterQuery
     */
    public function search(string $text): BaseFilterQuery
    {
        /** @var \App\Model\Product\Search\FilterQuery $clone */
        $clone = clone $this;

        $clone->match = [
            'multi_match' => [
                'query' => $text,
                'fields' => [
                    'name.full_with_diacritic^60',
                    'name.full_without_diacritic^50',
                    'name^45',
                    'name.edge_ngram_with_diacritic^40',
                    'name.edge_ngram_without_diacritic^35',
                    'catnum^50',
                    'catnum.ngram^25',
                    'partno^40',
                    'partno.edge_ngram^20',
                    'ean^60',
                    'ean.edge_ngram^30',
                    'short_description^5',
                    'description^5',
                    'variants_aliases.full_with_diacritic^60',
                    'variants_aliases.full_without_diacritic^50',
                    'variants_aliases^45',
                    'variants_aliases.edge_ngram_with_diacritic^40',
                    'variants_aliases.edge_ngram_without_diacritic^35',
                ],
            ],
        ];

        return $clone;
    }

    /**
     * @param string $orderingModeId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \App\Model\Product\Search\FilterQuery
     */
    public function applyOrdering(string $orderingModeId, PricingGroup $pricingGroup): BaseFilterQuery
    {
        $clone = clone $this;

        if ($orderingModeId === ProductListOrderingConfig::ORDER_BY_PRIORITY) {
            $clone->sorting = [
                'ordering_priority' => 'desc',
                'name.keyword' => 'asc',
            ];

            return $clone;
        }

        if ($orderingModeId === ProductListOrderingConfig::ORDER_BY_PRICE_ASC) {
            $clone->sorting = [
                'prices.price_with_vat' => [
                    'order' => 'asc',
                    'nested' => [
                        'path' => 'prices',
                        'filter' => [
                            'term' => [
                                'prices.pricing_group_id' => $pricingGroup->getId(),
                            ],
                        ],
                    ],
                ],
                'ordering_priority' => 'asc',
                'name.keyword' => 'asc',
            ];

            return $clone;
        }

        if ($orderingModeId === ProductListOrderingConfig::ORDER_BY_NEWEST) {
            $clone->sorting = [
                'selling_from' => 'desc',
                'name.keyword' => 'asc',
            ];

            return $clone;
        }

        return $clone;
    }

    /**
     * @return \App\Model\Product\Search\FilterQuery
     */
    public function applyDefaultOrdering(): BaseFilterQuery
    {
        $clone = clone $this;

        $clone->sorting = [
            'selling_from' => 'desc',
            'name.keyword' => 'asc',
        ];

        return $clone;
    }

    /**
     * @param array $ids
     * @return \App\Model\Product\Search\FilterQuery
     */
    public function filterIds(array $ids): self
    {
        $clone = clone $this;

        $clone->filters[] = [
            'terms' => [
                '_id' => $ids,
            ],
        ];

        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function getQuery(): array
    {
        $query = parent::getQuery();
        unset($query['type']);

        return $query;
    }

    /**
     * @inheritDoc
     */
    public function getAbsoluteNumbersAggregationQuery(): array
    {
        $query = parent::getAbsoluteNumbersAggregationQuery();
        unset($query['type']);

        return $query;
    }

    /**
     * @inheritDoc
     */
    public function getFlagsPlusNumbersQuery(array $selectedFlags): array
    {
        $query = parent::getFlagsPlusNumbersQuery($selectedFlags);
        unset($query['type']);

        return $query;
    }

    /**
     * @inheritDoc
     */
    public function getBrandsPlusNumbersQuery(array $selectedBrandsIds): array
    {
        $query = parent::getBrandsPlusNumbersQuery($selectedBrandsIds);
        unset($query['type']);

        return $query;
    }

    /**
     * @inheritDoc
     */
    public function getParametersPlusNumbersQuery(int $selectedParameterId, array $selectedValuesIds): array
    {
        $query = parent::getParametersPlusNumbersQuery($selectedParameterId, $selectedValuesIds);
        unset($query['type']);

        return $query;
    }
}
