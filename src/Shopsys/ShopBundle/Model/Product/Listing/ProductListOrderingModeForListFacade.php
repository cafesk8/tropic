<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Listing;

use Shopsys\FrameworkBundle\Model\Product\Listing\ProductListOrderingModeForListFacade as BaseProductListOrderingModeForListFacade;

class ProductListOrderingModeForListFacade extends BaseProductListOrderingModeForListFacade
{
    /**
     * @return \Shopsys\ShopBundle\Model\Product\Listing\ProductListOrderingConfig
     */
    public function getProductListOrderingConfig(): ProductListOrderingConfig
    {
        return new ProductListOrderingConfig(
            [
                ProductListOrderingConfig::ORDER_BY_NEWEST => t('Od nejnovějších'),
                ProductListOrderingConfig::ORDER_BY_PRICE_ASC => t('Od nejlevnějších'),
                ProductListOrderingConfig::ORDER_BY_PRIORITY => t('Od nejprodávanějších'),
            ],
            ProductListOrderingConfig::ORDER_BY_NEWEST,
            static::COOKIE_NAME
        );
    }
}