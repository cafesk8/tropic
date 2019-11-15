<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\StoreStock\Transfer;

use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse;

class AllGermanStoreStockImportCronModule extends AbstractAllOnDomainStoreStockImportCronModule
{
    public const TRANSFER_IDENTIFIER = 'import_product_store_stock_german';

    /**
     * @return \Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse
     */
    protected function getTransferResponse(): TransferResponse
    {
        $this->logger->addInfo('Downloading stock quantities from IS for German domain');

        return $this->getTransferResponseByRestClient($this->multidomainRestClient->getGermanRestClient());
    }
}
