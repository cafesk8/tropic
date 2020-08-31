<?php

declare(strict_types=1);

namespace App\Model\Product\BestsellingProduct;

use Shopsys\FrameworkBundle\Model\Product\BestsellingProduct\BestsellingProductFacade as BaseBestsellingProductFacade;

/**
 * @method \App\Model\Product\Product[] getAllOfferedBestsellingProducts(int $domainId, \App\Model\Category\Category $category, \App\Model\Pricing\Group\PricingGroup $pricingGroup)
 */
class BestsellingProductFacade extends BaseBestsellingProductFacade
{
    protected const MAX_RESULTS = 6;
    public const MAX_SHOW_RESULTS = 6;
}
