<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Search;

use Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery as BaseFilterQuery;
use Shopsys\FrameworkBundle\Model\Product\Search\FilterQueryFactory as BaseFilterQueryFactory;

class FilterQueryFactory extends BaseFilterQueryFactory
{
    /**
     * @param string $indexName
     * @return \Shopsys\ShopBundle\Model\Product\Search\FilterQuery
     */
    public function create(string $indexName): BaseFilterQuery
    {
        return new FilterQuery($indexName);
    }
}
