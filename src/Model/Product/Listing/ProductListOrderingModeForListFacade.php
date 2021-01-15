<?php

declare(strict_types=1);

namespace App\Model\Product\Listing;

use Shopsys\FrameworkBundle\Model\Product\Listing\ProductListOrderingModeForListFacade as BaseProductListOrderingModeForListFacade;
use Symfony\Component\HttpFoundation\Request;

class ProductListOrderingModeForListFacade extends BaseProductListOrderingModeForListFacade
{
    /**
     * @param bool $forNews
     * @return \App\Model\Product\Listing\ProductListOrderingConfig
     */
    public function getProductListOrderingConfig(bool $forNews = false): ProductListOrderingConfig
    {
        if ($forNews) {
            return new ProductListOrderingConfig(
                [
                    ProductListOrderingConfig::ORDER_BY_NEWS_ACTIVE_FROM => t('Nejnovější'),
                    ProductListOrderingConfig::ORDER_BY_PRICE_ASC => t('Nejlevnější'),
                    ProductListOrderingConfig::ORDER_BY_PRICE_DESC => t('Nejdražší'),
                ],
                ProductListOrderingConfig::ORDER_BY_NEWS_ACTIVE_FROM,
                static::COOKIE_NAME
            );
        }

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

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param bool $forNews
     * @return string
     */
    public function getOrderingModeIdFromRequest(Request $request, bool $forNews = false): string
    {
        return $this->requestToOrderingModeIdConverter->getOrderingModeIdFromRequest(
            $request,
            $this->getProductListOrderingConfig($forNews)
        );
    }
}
