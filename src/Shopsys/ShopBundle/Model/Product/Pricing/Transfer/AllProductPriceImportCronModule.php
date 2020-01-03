<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Pricing\Transfer;

class AllProductPriceImportCronModule extends AbstractProductPriceImportCronModule
{
    public const TRANSFER_IDENTIFIER = 'import_product_prices';

    /**
     * @return string
     */
    protected function getTransferIdentifier(): string
    {
        return self::TRANSFER_IDENTIFIER;
    }

    /**
     * @return string
     */
    protected function getApiUrl(): string
    {
        return '/api/Eshop/ArticlePrices';
    }
}
