<?php

declare(strict_types=1);

namespace App\Model\Product\Listing;

use Shopsys\FrameworkBundle\Model\Product\Listing\ProductListOrderingModeForBrandFacade as BaseProductListOrderingModeForBrandFacade;

class ProductListOrderingModeForBrandFacade extends BaseProductListOrderingModeForBrandFacade
{
    /**
     * @return \App\Model\Product\Listing\ProductListOrderingConfig
     */
    public function getProductListOrderingConfig(): ProductListOrderingConfig
    {
        return new ProductListOrderingConfig(
            [
                ProductListOrderingConfig::ORDER_BY_PRIORITY => t('Doporučené'),
                ProductListOrderingConfig::ORDER_BY_PRICE_ASC => t('Nejlevnější'),
                ProductListOrderingConfig::ORDER_BY_PRICE_DESC => t('Nejdražší'),
            ],
            ProductListOrderingConfig::ORDER_BY_PRIORITY,
            static::COOKIE_NAME
        );
    }
}
