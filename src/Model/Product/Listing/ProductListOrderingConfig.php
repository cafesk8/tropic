<?php

declare(strict_types=1);

namespace App\Model\Product\Listing;

use Shopsys\FrameworkBundle\Model\Product\Listing\ProductListOrderingConfig as BaseProductListOrderingConfig;

class ProductListOrderingConfig extends BaseProductListOrderingConfig
{
    public const ORDER_BY_NEWEST = 'newest';
    public const ORDER_BY_NEWS_ACTIVE_FROM = 'news_active_from';
}
