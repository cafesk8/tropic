<?php

declare(strict_types=1);

namespace App\Model\Product\Search;

use App\Model\Product\Listing\ProductListOrderingConfig;
use App\Model\Product\Product;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery as BaseFilterQuery;

/**
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
    protected const MAXIMUM_REASONABLE_AGGREGATION_BUCKET_COUNT = 1000;

    /**
     * @param string $text
     * @return \App\Model\Product\Search\FilterQuery
     */
    public function search(string $text): BaseFilterQuery
    {
        /** @var \App\Model\Product\Search\FilterQuery $clone */
        $clone = clone $this;
        $text = str_replace('/', '\/', $text);

        $clone->match = [
            'multi_match' => [
                'query' => $text,
                'fields' => [
                    'name.full_with_diacritic^60',
                    'name.full_without_diacritic^50',
                    'name^45',
                    'name.edge_ngram_with_diacritic^40',
                    'name.edge_ngram_without_diacritic^35',
                    'catnum^70',
                    'catnum.ngram^65',
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
                'internal_stocks_quantity' => 'desc',
                'external_stocks_quantity' => 'desc',
                'id' => 'desc',
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

        if ($orderingModeId === ProductListOrderingConfig::ORDER_BY_PRICE_DESC) {
            $clone->sorting = [
                'prices.price_with_vat' => [
                    'order' => 'desc',
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
     * @return \App\Model\Product\Search\FilterQuery
     */
    public function filterOnlyInSale(): self
    {
        $clone = clone $this;

        $clone->filters[] = [
            'term' => [
                'is_in_any_sale_stock' => true,
            ],
        ];

        return $clone;
    }

    /**
     * @return \App\Model\Product\Search\FilterQuery
     */
    public function filterOnlyInNews(): self
    {
        $clone = clone $this;

        $clone->filters[] = [
            'term' => [
                'is_in_news' => true,
            ],
        ];

        return $clone;
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

    /**
     * The difference with the parent method is that we use prices_for_filter here instead of prices field.
     * Thanks to that, we are able to filter main variants by their sellable variant prices, regardless the main variant "price from"
     *
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $minimalPrice
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $maximalPrice
     * @return \App\Model\Product\Search\FilterQuery
     */
    public function filterByPrices(PricingGroup $pricingGroup, ?Money $minimalPrice = null, ?Money $maximalPrice = null): BaseFilterQuery
    {
        $clone = clone $this;

        $prices = [];
        if ($minimalPrice !== null) {
            $prices['gte'] = (float)$minimalPrice->getAmount();
        }
        if ($maximalPrice !== null) {
            $prices['lte'] = (float)$maximalPrice->getAmount();
        }

        $clone->filters[] = [
            'nested' => [
                'path' => 'prices_for_filter',
                'query' => [
                    'bool' => [
                        'must' => [
                            'match_all' => new \stdClass(),
                        ],
                        'filter' => [
                            [
                                'term' => [
                                    'prices_for_filter.pricing_group_id' => $pricingGroup->getId(),
                                ],
                            ],
                            [
                                'range' => [
                                    'prices_for_filter.price_with_vat' => $prices,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $clone;
    }

    /**
     * @param int $pohodaProductType
     * @return \App\Model\Product\Search\FilterQuery
     */
    public function filterByPohodaProductType(int $pohodaProductType): self
    {
        $clone = clone $this;

        $clone->filters[] = [
            'term' => [
                'pohoda_product_type' => $pohodaProductType,
            ],
        ];

        return $clone;
    }

    /**
     * @return \App\Model\Product\Search\FilterQuery
     */
    public function excludeVariants(): self
    {
        $clone = clone $this;

        $clone->filters[] = [
            'terms' => [
                'variant_type' => [Product::VARIANT_TYPE_NONE, Product::VARIANT_TYPE_MAIN],
            ],
        ];

        return $clone;
    }

    /**
     * @return \App\Model\Product\Search\FilterQuery
     */
    public function excludeMainVariants(): self
    {
        $clone = clone $this;

        $clone->filters[] = [
            'terms' => [
                'variant_type' => [Product::VARIANT_TYPE_NONE, Product::VARIANT_TYPE_VARIANT],
            ],
        ];

        return $clone;
    }

    /**
     * @param int $pricingGroupId
     * @return array
     */
    public function getFilterQuery(int $pricingGroupId): array
    {
        return [
            'index' => $this->indexName,
            'body' => [
                'size' => 0,
                'aggs' => [
                    'parameters' => [
                        'nested' => [
                            'path' => 'parameters',
                        ],
                        'aggs' => [
                            'by_parameters' => [
                                'terms' => [
                                    'field' => 'parameters.parameter_id',
                                    'size' => static::MAXIMUM_REASONABLE_AGGREGATION_BUCKET_COUNT,
                                ],
                                'aggs' => [
                                    'by_value' => [
                                        'terms' => [
                                            'field' => 'parameters.parameter_value_id',
                                            'size' => static::MAXIMUM_REASONABLE_AGGREGATION_BUCKET_COUNT,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'flags' => [
                        'terms' => [
                            'field' => 'flags',
                            'size' => static::MAXIMUM_REASONABLE_AGGREGATION_BUCKET_COUNT,
                        ],
                    ],
                    'brands' => [
                        'terms' => [
                            'field' => 'brand',
                            'size' => static::MAXIMUM_REASONABLE_AGGREGATION_BUCKET_COUNT,
                        ],
                    ],
                    'stock' => [
                        'filter' => [
                            'term' => [
                                'in_stock' => 'true',
                            ],
                        ],
                    ],
                    'prices_for_filter' => [
                        'nested' => [
                            'path' => 'prices_for_filter',
                        ],
                        'aggs' => [
                            'filter_pricing_group' => [
                                'filter' => [
                                    'term' => [
                                        'prices_for_filter.pricing_group_id' => $pricingGroupId,
                                    ],
                                ],
                                'aggs' => [
                                    'min_price' => [
                                        'min' => [
                                            'field' => 'prices_for_filter.price_with_vat',
                                        ],
                                    ],
                                    'max_price' => [
                                        'max' => [
                                            'field' => 'prices_for_filter.price_with_vat',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'query' => [
                    'bool' => [
                        'must' => $this->match,
                        'filter' => $this->filters,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array $parameters
     * @return \App\Model\Product\Search\FilterQuery
     */
    public function filterByParameters(array $parameters): self
    {
        $clone = clone $this;
        $paramFilters = [];

        foreach ($parameters as $parameterId => $parameterValues) {
            $paramFilters[] = [
                'nested' => [
                    'path' => 'parameters_for_filter.parameter_groups',
                    'query' => [
                        'bool' => [
                            'must' => [
                                [
                                    'term' => [
                                        'parameters_for_filter.parameter_groups.parameter_id' => $parameterId,
                                    ],
                                ],
                                [
                                    'terms' => [
                                        'parameters_for_filter.parameter_groups.parameter_value_id' => $parameterValues,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }

        if (!empty($paramFilters)) {
            $clone->filters[] = [
                'nested' => [
                    'path' => 'parameters_for_filter',
                    'query' => [
                        'bool' => [
                            'must' => $paramFilters,
                        ],
                    ],
                ],
            ];
        }

        return $clone;
    }
}
