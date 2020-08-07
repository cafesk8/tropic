<?php

declare(strict_types=1);

namespace App\Model\Product\Listing;

use Shopsys\FrameworkBundle\Model\Product\Listing\ProductListOrderingModeForSearchFacade as BaseProductListOrderingModeForSearchFacade;

class ProductListOrderingModeForSearchFacade extends BaseProductListOrderingModeForSearchFacade
{
    /**
     * @return \App\Model\Product\Listing\ProductListOrderingConfig
     */
    public function getProductListOrderingConfig(): ProductListOrderingConfig
    {
        return new ProductListOrderingConfig(
            [
                ProductListOrderingConfig::ORDER_BY_RELEVANCE => t('Relevance'),
                ProductListOrderingConfig::ORDER_BY_PRIORITY => t('Doporučené'),
                ProductListOrderingConfig::ORDER_BY_PRICE_ASC => t('Nejlevnější'),
                ProductListOrderingConfig::ORDER_BY_PRICE_DESC => t('Nejdražší'),
            ],
            ProductListOrderingConfig::ORDER_BY_RELEVANCE,
            static::COOKIE_NAME
        );
    }
}
