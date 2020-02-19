<?php

declare(strict_types=1);

namespace App\Model\Product\Search;

use App\Model\Product\Listing\ProductListOrderingConfig;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery as BaseFilterQuery;

class FilterQuery extends BaseFilterQuery
{
    /**
     * @param string $orderingModeId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery
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
     * @return \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery
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
}