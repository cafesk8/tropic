<?php

declare(strict_types=1);

namespace App\Model\Product\StoreStock\Transfer;

use App\Component\Transfer\Response\TransferResponse;

class AllCzechStoreStockImportCronModule extends AbstractAllOnDomainStoreStockImportCronModule
{
    public const TRANSFER_IDENTIFIER = 'import_product_store_stock_czech';

    /**
     * @return \App\Component\Transfer\Response\TransferResponse
     */
    protected function getTransferResponse(): TransferResponse
    {
        $this->logger->addInfo('Downloading stock quantities from IS for Czech domain');

        return $this->getTransferResponseByRestClient($this->multidomainRestClient->getCzechRestClient());
    }
}
