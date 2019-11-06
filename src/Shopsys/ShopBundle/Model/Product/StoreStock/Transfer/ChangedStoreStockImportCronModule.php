<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\StoreStock\Transfer;

class ChangedStoreStockImportCronModule extends AbstractStoreStockImportCronModule
{
    public const TRANSFER_IDENTIFIER = 'import_product_store_stock_changed';

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
        return '/api/Eshop/ChangedStockQuantityBySites';
    }
}
