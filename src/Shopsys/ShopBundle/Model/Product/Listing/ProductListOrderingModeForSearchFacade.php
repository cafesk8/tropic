<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Listing;

use Shopsys\FrameworkBundle\Model\Product\Listing\ProductListOrderingModeForSearchFacade as BaseProductListOrderingModeForSearchFacade;

class ProductListOrderingModeForSearchFacade extends BaseProductListOrderingModeForSearchFacade
{
    /**
     * @return \Shopsys\ShopBundle\Model\Product\Listing\ProductListOrderingConfig
     */
    public function getProductListOrderingConfig(): ProductListOrderingConfig
    {
        return new ProductListOrderingConfig(
            [
                ProductListOrderingConfig::ORDER_BY_PRIORITY => t('Od nejprodávanějších'),
                ProductListOrderingConfig::ORDER_BY_NEWEST => t('Od nejnovějších'),
                ProductListOrderingConfig::ORDER_BY_PRICE_ASC => t('Od nejlevnějších'),
            ],
            ProductListOrderingConfig::ORDER_BY_PRIORITY,
            static::COOKIE_NAME
        );
    }
}
