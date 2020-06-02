<?php

declare(strict_types=1);

namespace App\Model\Product;

use Shopsys\FrameworkBundle\Model\Product\Product as BaseProduct;
use Shopsys\FrameworkBundle\Model\Product\ProductHiddenRecalculator as BaseProductHiddenRecalculator;

class ProductHiddenRecalculator extends BaseProductHiddenRecalculator
{
    /**
     * This method is called from framework but we don't need it to do anything
     *
     * @param \App\Model\Product\Product $product
     */
    public function calculateHiddenForProduct(BaseProduct $product)
    {
    }

    /**
     * This method is called from framework but we don't need it to do anything
     */
    public function calculateHiddenForAll()
    {
    }

    /**
     * This method originally calculates calculatedHidden property of Product but we use shown property of ProductDomain instead in this project
     *
     * @param \App\Model\Product\Product|null $product
     * @deprecated https://gitlab.shopsys.cz/ss6-projects/tropic-fishing/-/merge_requests/123#note_241223
     */
    protected function executeQuery(?BaseProduct $product = null)
    {
        @trigger_error('Deprecated, Product::calculatedHidden is replaced by ProductDomain::shown in this project', E_USER_DEPRECATED);
    }
}
